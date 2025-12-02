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

class MarkAttendancePercentage extends Command
{
    protected $raffleService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-percentage 
                            {--event= : ID del evento específico (opcional)}
                            {--percentage=89 : Porcentaje de invitados a marcar como asistentes}
                            {--dry-run : Mostrar qué se haría sin ejecutar los cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca un porcentaje de invitados como asistentes simulando escaneos reales (por defecto 89%)';

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
        $eventId = $this->option('event');
        $percentage = (float) $this->option('percentage');
        $isDryRun = $this->option('dry-run');

        if ($percentage < 0 || $percentage > 100) {
            $this->error('El porcentaje debe estar entre 0 y 100');
            return Command::FAILURE;
        }

        $this->info("🎯 Marcando {$percentage}% de invitados como asistentes...");

        // Obtener eventos a procesar
        if ($eventId) {
            $events = Event::where('id', $eventId)->get();
            if ($events->isEmpty()) {
                $this->error("No se encontró el evento con ID: {$eventId}");
                return Command::FAILURE;
            }
        } else {
            $events = Event::all();
            if ($events->isEmpty()) {
                $this->warn('No se encontraron eventos en el sistema');
                return Command::SUCCESS;
            }
        }

        $totalProcessed = 0;
        $totalMarked = 0;

        foreach ($events as $event) {
            $this->line("");
            $this->info("📅 Procesando evento: {$event->name} (ID: {$event->id})");

            // Obtener invitados que NO tienen asistencia registrada
            $guestsWithoutAttendance = Guest::where('event_id', $event->id)
                ->whereDoesntHave('attendance')
                ->get();

            $totalGuests = $guestsWithoutAttendance->count();

            if ($totalGuests === 0) {
                $this->warn("   ⚠️  No hay invitados sin asistencia registrada en este evento");
                continue;
            }

            // Calcular cuántos invitados marcar (89% redondeado hacia arriba)
            $toMark = (int) ceil($totalGuests * ($percentage / 100));
            
            $this->line("   📊 Total de invitados sin asistencia: {$totalGuests}");
            $this->line("   🎯 Invitados a marcar como asistentes: {$toMark} ({$percentage}%)");

            if ($isDryRun) {
                $this->warn("   🔍 DRY RUN - No se realizarán cambios");
                $totalProcessed += $totalGuests;
                $totalMarked += $toMark;
                continue;
            }

            // Seleccionar aleatoriamente los invitados a marcar
            $guestsToMark = $guestsWithoutAttendance->random(min($toMark, $totalGuests));

            // Obtener premios activos del evento para crear entradas de rifa
            $activePrizes = $event->prizes()->where('active', true)->get();

            // Calcular rango de tiempo para simular escaneos distribuidos
            // Si el evento tiene fecha y hora de inicio, usar esos valores
            $eventStartTime = $event->event_date && $event->start_time 
                ? Carbon::parse($event->event_date->format('Y-m-d') . ' ' . $event->start_time->format('H:i:s'))
                : now()->subHours(2); // Por defecto, simular escaneos en las últimas 2 horas
            
            $eventEndTime = $event->event_date && $event->end_time
                ? Carbon::parse($event->event_date->format('Y-m-d') . ' ' . $event->end_time->format('H:i:s'))
                : now(); // Por defecto, hasta ahora
            
            // Asegurar que el tiempo de inicio no sea mayor que el de fin
            if ($eventStartTime->gt($eventEndTime)) {
                $eventStartTime = $eventEndTime->copy()->subHours(2);
            }

            // Registrar asistencias
            $marked = 0;
            $raffleEntriesCreated = 0;
            try {
                DB::beginTransaction();

                foreach ($guestsToMark as $index => $guest) {
                    // Simular tiempo de escaneo distribuido a lo largo del evento
                    // Usar distribución aleatoria entre el inicio y fin del evento
                    $minutesFromStart = rand(0, max(1, $eventStartTime->diffInMinutes($eventEndTime)));
                    $scannedAt = $eventStartTime->copy()->addMinutes($minutesFromStart);
                    
                    // Asegurar que no sea en el futuro
                    if ($scannedAt->gt(now())) {
                        $scannedAt = now()->subSeconds(rand(1, 300)); // Últimos 5 minutos
                    }

                    // Simular IP y user agent realistas
                    $simulatedIP = $this->generateRandomIP();
                    $simulatedUserAgent = $this->generateRandomUserAgent();

                    // Crear asistencia simulando escaneo real
                    $attendance = Attendance::create([
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'scanned_at' => $scannedAt,
                        'scanned_by' => 'Scanner QR',
                        'scan_metadata' => [
                            'method' => 'qr_scan',
                            'ip' => $simulatedIP,
                            'user_agent' => $simulatedUserAgent,
                            'simulated' => true,
                            'simulated_at' => now()->toIso8601String()
                        ]
                    ]);

                    // Crear participaciones automáticamente para todos los premios activos del evento
                    // Solo si el invitado es elegible para cada premio (igual que en AttendanceController)
                    // Optimizado: verificar elegibilidad directamente sin cargar todos los elegibles
                    foreach ($activePrizes as $prize) {
                        try {
                            // Verificar si ya tiene una entrada para este premio
                            $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                                ->where('prize_id', $prize->id)
                                ->first();
                            
                            // Si ya tiene entrada, saltar
                            if ($existingEntry) {
                                continue;
                            }
                            
                            // Verificar elegibilidad de forma más eficiente
                            // Solo verificar las reglas básicas sin cargar todos los elegibles
                            $isEligible = $this->isGuestEligibleForPrize($guest, $prize, 'general');
                            
                            if ($isEligible) {
                                RaffleEntry::enterRaffle($guest, $prize, [
                                    'auto_entered' => true,
                                    'entered_by' => 'attendance_scan',
                                    'attendance_id' => $attendance->id
                                ]);
                                $raffleEntriesCreated++;
                            }
                        } catch (\Exception $raffleError) {
                            // Log el error pero no fallar el registro de asistencia
                            Log::warning('Error al crear participación automática al simular escaneo', [
                                'guest_id' => $guest->id,
                                'prize_id' => $prize->id,
                                'event_id' => $event->id,
                                'error' => $raffleError->getMessage()
                            ]);
                        }
                    }

                    $marked++;
                }

                DB::commit();

                // Registrar en auditoría
                try {
                    AuditLog::create([
                        'user_id' => null,
                        'action' => 'bulk_mark_attendance',
                        'model' => 'Attendance',
                        'model_id' => null,
                        'description' => "Marcado masivo de asistencia: {$marked} invitados ({$percentage}%) en evento {$event->name}",
                        'ip_address' => null,
                        'user_agent' => 'Artisan Command: attendance:mark-percentage',
                    ]);
                } catch (\Exception $auditError) {
                    // No fallar si el log de auditoría falla
                    Log::warning('Error al registrar en auditoría: ' . $auditError->getMessage());
                }

                $this->info("   ✅ Se marcaron {$marked} invitados como asistentes");
                if ($raffleEntriesCreated > 0) {
                    $this->info("   🎲 Se crearon {$raffleEntriesCreated} entradas de rifa automáticamente");
                }
                $totalProcessed += $totalGuests;
                $totalMarked += $marked;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("   ❌ Error al marcar asistencias: " . $e->getMessage());
                Log::error('Error en comando mark-percentage', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->line("");
        if ($isDryRun) {
            $this->warn("🔍 DRY RUN COMPLETADO");
            $this->info("Total de invitados que se marcarían: {$totalMarked} de {$totalProcessed}");
            $this->info("Ejecuta sin --dry-run para aplicar los cambios");
        } else {
            $this->info("✅ PROCESO COMPLETADO");
            $this->info("Total de invitados marcados como asistentes: {$totalMarked} de {$totalProcessed}");
        }

        return Command::SUCCESS;
    }

    /**
     * Genera una IP aleatoria realista
     */
    private function generateRandomIP(): string
    {
        $ips = [
            '192.168.1.' . rand(100, 254),
            '10.0.0.' . rand(1, 254),
            '172.16.' . rand(0, 31) . '.' . rand(1, 254),
        ];
        return $ips[array_rand($ips)];
    }

    /**
     * Genera un User Agent aleatorio realista
     */
    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Android 13; Mobile; rv:109.0) Gecko/109.0 Firefox/120.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Verifica si un invitado es elegible para un premio de forma eficiente
     * Sin cargar todos los elegibles, solo verifica las reglas básicas
     */
    private function isGuestEligibleForPrize(Guest $guest, Prize $prize, string $raffleType = 'general'): bool
    {
        // REGLA COMÚN: Debe tener asistencia (ya la tiene porque estamos en el proceso de marcarla)
        
        // REGLAS DE RIFA GENERAL
        if ($raffleType === 'general') {
            // REGLA 3: Solo "General", "Subdirectores" o "IMEX"
            if (!in_array($guest->descripcion, ['General', 'Subdirectores', 'IMEX'])) {
                return false;
            }
            
            // REGLA 9: No puede ser INV
            if ($guest->compania === 'INV') {
                return false;
            }
            
            // REGLA 2: No pueden participar ganadores de la Rifa Pública
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

