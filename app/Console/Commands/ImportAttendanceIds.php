<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Services\RaffleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportAttendanceIds extends Command
{
    protected $raffleService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:import-ids {event_id : ID del evento}
                            {file : Ruta del archivo con los números de empleado (uno por línea)}
                            {--dry-run : Mostrar qué se haría sin ejecutar los cambios}
                            {--create-raffle-entries : Crear entradas de rifa automáticamente para los invitados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar números de empleado desde un archivo y marcar asistencias para replicar un escenario de rifa';

    public function __construct(RaffleService $raffleService)
    {
        parent::__construct();
        $this->raffleService = $raffleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $createRaffleEntries = $this->option('create-raffle-entries');

        // Buscar el evento
        $event = Event::find($eventId);
        
        if (!$event) {
            $this->error("❌ No se encontró el evento con ID: {$eventId}");
            return 1;
        }

        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            $this->error("❌ El archivo no existe: {$filePath}");
            return 1;
        }

        $this->info("📥 Importando números de empleado para el evento: {$event->name} (ID: {$eventId})");
        $this->line("   📁 Archivo: {$filePath}");
        $this->newLine();

        // Leer el archivo
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $this->error("❌ No se pudo leer el archivo: {$filePath}");
            return 1;
        }

        // Procesar los números de empleado (uno por línea, ignorar líneas vacías)
        $employeeNumbers = collect(explode("\n", $fileContent))
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => !empty($line))
            ->unique()
            ->values();

        if ($employeeNumbers->isEmpty()) {
            $this->warn("⚠️  No se encontraron números de empleado válidos en el archivo.");
            return 0;
        }

        $this->info("📊 Total de números de empleado únicos encontrados en el archivo: " . $employeeNumbers->count());
        $this->newLine();

        // Buscar los guests por número de empleado y que pertenezcan al evento
        $validGuests = Guest::where('event_id', $eventId)
            ->whereIn('numero_empleado', $employeeNumbers)
            ->get()
            ->keyBy('numero_empleado');

        $invalidNumbers = $employeeNumbers->diff($validGuests->keys());
        $validNumbers = $employeeNumbers->intersect($validGuests->keys());

        if ($invalidNumbers->isNotEmpty()) {
            $this->warn("⚠️  Se encontraron " . $invalidNumbers->count() . " números de empleado que no pertenecen a este evento:");
            $this->line("   " . $invalidNumbers->take(10)->implode(', ') . ($invalidNumbers->count() > 10 ? '...' : ''));
            $this->newLine();
        }

        if ($validNumbers->isEmpty()) {
            $this->error("❌ No hay números de empleado válidos para procesar.");
            return 1;
        }

        $this->info("✅ Números de empleado válidos a procesar: " . $validNumbers->count());
        $this->newLine();

        // Obtener los IDs de los guests válidos
        $validGuestIds = $validGuests->pluck('id');

        // Verificar cuántos ya tienen asistencia
        $existingAttendances = Attendance::where('event_id', $eventId)
            ->whereIn('guest_id', $validGuestIds)
            ->pluck('guest_id');

        $toCreate = $validGuestIds->diff($existingAttendances);
        $alreadyExist = $validGuestIds->intersect($existingAttendances);

        if ($alreadyExist->isNotEmpty()) {
            $this->warn("⚠️  " . $alreadyExist->count() . " invitados ya tienen asistencia registrada (se omitirán)");
        }

        if ($toCreate->isEmpty()) {
            $this->warn("⚠️  Todos los invitados ya tienen asistencia registrada.");
            return 0;
        }

        $this->info("📝 Asistencias a crear: " . $toCreate->count());
        $this->newLine();

        if ($isDryRun) {
            $this->warn("🔍 DRY RUN - No se realizarán cambios");
            $this->newLine();
            $this->info("Se crearían asistencias para los siguientes números de empleado:");
            $guestsToCreate = Guest::whereIn('id', $toCreate)->pluck('numero_empleado');
            $this->line("   " . $guestsToCreate->take(20)->implode(', ') . ($guestsToCreate->count() > 20 ? '...' : ''));
            return 0;
        }

        // Confirmar antes de proceder
        if (!$this->confirm('¿Deseas continuar con la creación de asistencias?', true)) {
            $this->warn('⚠️  Operación cancelada');
            return 0;
        }

        $this->newLine();
        $this->info('🚀 Creando asistencias...');
        $this->newLine();

        // Obtener premios activos si se requiere crear entradas de rifa
        $activePrizes = collect();
        if ($createRaffleEntries) {
            $activePrizes = $event->prizes()->where('active', true)->get();
            $this->info("🎲 Se crearán entradas de rifa automáticamente para " . $activePrizes->count() . " premios activos");
            $this->newLine();
        }

        // Calcular rango de tiempo para simular escaneos distribuidos
        $eventDate = $event->event_date;
        $startTime = $event->start_time;
        $endTime = $event->end_time;
        
        if ($eventDate instanceof Carbon && $startTime instanceof Carbon) {
            $eventStartTime = Carbon::parse($eventDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
        } else {
            $eventStartTime = now()->subHours(2);
        }
        
        if ($eventDate instanceof Carbon && $endTime instanceof Carbon) {
            $eventEndTime = Carbon::parse($eventDate->format('Y-m-d') . ' ' . $endTime->format('H:i:s'));
        } else {
            $eventEndTime = now();
        }

        if ($eventStartTime->gt($eventEndTime)) {
            $eventStartTime = $eventEndTime->copy()->subHours(2);
        }

        $bar = $this->output->createProgressBar($toCreate->count());
        $bar->start();

        $created = 0;
        $errors = 0;
        $raffleEntriesCreated = 0;

        try {
            DB::beginTransaction();

            // Obtener los guests a crear por ID
            $guestsToCreate = Guest::whereIn('id', $toCreate)->get()->keyBy('id');

            foreach ($toCreate as $guestId) {
                try {
                    $guest = $guestsToCreate[$guestId];

                    // Simular tiempo de escaneo distribuido
                    $minutesFromStart = rand(0, max(1, $eventStartTime->diffInMinutes($eventEndTime)));
                    $scannedAt = $eventStartTime->copy()->addMinutes($minutesFromStart);
                    
                    if ($scannedAt->gt(now())) {
                        $scannedAt = now()->subSeconds(rand(1, 300));
                    }

                    // Crear asistencia
                    $attendance = Attendance::create([
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'scanned_at' => $scannedAt,
                        'scanned_by' => 'Importación masiva',
                        'scan_count' => 1,
                        'last_scanned_at' => $scannedAt,
                        'scan_metadata' => [
                            'method' => 'bulk_import',
                            'imported_from_file' => basename($filePath),
                            'imported_at' => now()->toIso8601String(),
                            'ip' => '127.0.0.1',
                            'user_agent' => 'Artisan Command: attendance:import-ids'
                        ]
                    ]);

                    // Crear entradas de rifa si se solicita
                    if ($createRaffleEntries) {
                        foreach ($activePrizes as $prize) {
                            try {
                                $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                                    ->where('prize_id', $prize->id)
                                    ->first();
                                
                                if ($existingEntry) {
                                    continue;
                                }
                                
                                $isEligible = $this->isGuestEligibleForPrize($guest, $prize, 'general');
                                
                                if ($isEligible) {
                                    RaffleEntry::enterRaffle($guest, $prize, [
                                        'auto_entered' => true,
                                        'entered_by' => 'attendance_import',
                                        'attendance_id' => $attendance->id
                                    ]);
                                    $raffleEntriesCreated++;
                                }
                            } catch (\Exception $raffleError) {
                                Log::warning('Error al crear participación automática', [
                                    'guest_id' => $guest->id,
                                    'prize_id' => $prize->id,
                                    'error' => $raffleError->getMessage()
                                ]);
                            }
                        }
                    }

                    $created++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error al crear asistencia', [
                        'guest_id' => $guestId,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            DB::commit();

            // Registrar en auditoría
            try {
                AuditLog::create([
                    'user_id' => null,
                    'action' => 'bulk_import_attendance',
                    'model' => 'Attendance',
                    'model_id' => null,
                    'description' => "Importación masiva de asistencias: {$created} invitados desde archivo " . basename($filePath) . " en evento {$event->name}",
                    'ip_address' => null,
                    'user_agent' => 'Artisan Command: attendance:import-ids',
                ]);
            } catch (\Exception $auditError) {
                Log::warning('Error al registrar en auditoría: ' . $auditError->getMessage());
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('✨ Proceso completado');
            $this->newLine();
            $this->table(
                ['Resultado', 'Cantidad'],
                [
                    ['✅ Asistencias creadas', $created],
                    ['❌ Errores', $errors],
                    ['📊 Total procesados', $toCreate->count()],
                ]
            );

            if ($createRaffleEntries && $raffleEntriesCreated > 0) {
                $this->newLine();
                $this->info("🎲 Entradas de rifa creadas: {$raffleEntriesCreated}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error al procesar: " . $e->getMessage());
            Log::error('Error en comando import-ids', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Verifica si un invitado es elegible para un premio
     */
    private function isGuestEligibleForPrize(Guest $guest, Prize $prize, string $raffleType = 'general'): bool
    {
        if ($raffleType === 'general') {
            if (!in_array($guest->descripcion, ['General', 'Subdirectores', 'IMEX'])) {
                return false;
            }
            
            if ($guest->compania === 'INV') {
                return false;
            }
            
            $hasWonPublicPrize = RaffleEntry::where('guest_id', $guest->id)
                ->whereHas('prize', function ($q) use ($prize) {
                    $q->where('event_id', $prize->event_id)
                      ->where('name', '!=', 'Rifa General');
                })
                ->where('status', 'won')
                ->exists();
            
            if ($hasWonPublicPrize) {
                return false;
            }
        }
        
        return true;
    }
}

