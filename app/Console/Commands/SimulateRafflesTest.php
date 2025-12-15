<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\Attendance;
use App\Models\RaffleLog;
use App\Services\GuestImportService;
use App\Services\PrizeImportService;
use App\Services\RaffleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class SimulateRafflesTest extends Command
{
    protected $raffleService;
    protected $guestImportService;
    protected $prizeImportService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:simulate-raffles 
                            {--count=20 : Número de rifas a simular}
                            {--guests-file=storage/app/exports/guests.csv : Ruta del archivo de guests}
                            {--attendances-file=storage/app/exports/asistencias.txt : Ruta del archivo de asistencias}
                            {--prizes-file=storage/app/exports/premios.csv : Ruta del archivo de premios}
                            {--general-winners=76 : Número de ganadores en la rifa general}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simular múltiples rifas completas para pruebas: crear eventos, importar datos, rifar premios y exportar ganadores';

    public function __construct(
        RaffleService $raffleService,
        GuestImportService $guestImportService,
        PrizeImportService $prizeImportService
    ) {
        parent::__construct();
        $this->raffleService = $raffleService;
        $this->guestImportService = $guestImportService;
        $this->prizeImportService = $prizeImportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $guestsFile = $this->option('guests-file');
        $attendancesFile = $this->option('attendances-file');
        $prizesFile = $this->option('prizes-file');
        $generalWinners = (int) $this->option('general-winners');

        $this->info("🎲 Iniciando simulación de {$count} rifas");
        $this->newLine();

        // Verificar que los archivos existan
        if (!file_exists($guestsFile)) {
            $this->error("❌ No se encontró el archivo de guests: {$guestsFile}");
            return 1;
        }

        if (!file_exists($attendancesFile)) {
            $this->error("❌ No se encontró el archivo de asistencias: {$attendancesFile}");
            return 1;
        }

        if (!file_exists($prizesFile)) {
            $this->error("❌ No se encontró el archivo de premios: {$prizesFile}");
            return 1;
        }

        $this->info("📁 Archivos encontrados:");
        $this->line("   • Guests: {$guestsFile}");
        $this->line("   • Asistencias: {$attendancesFile}");
        $this->line("   • Premios: {$prizesFile}");
        $this->newLine();

        $allWinners = [];
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        try {
            for ($i = 1; $i <= $count; $i++) {
                $this->newLine();
                $this->info("📋 Procesando Rifa #{$i} de {$count}");

                // Crear evento
                $event = $this->createEvent($i);
                $this->line("   ✅ Evento creado: {$event->name} (ID: {$event->id})");

                // Importar guests
                $guestsResult = $this->importGuests($event, $guestsFile);
                if (!$guestsResult['success']) {
                    $this->warn("   ⚠️  Error al importar guests: " . $guestsResult['error']);
                    continue;
                }
                $this->line("   ✅ Guests importados: {$guestsResult['imported']}");

                // Importar asistencias
                $attendancesResult = $this->importAttendances($event, $attendancesFile);
                if (!$attendancesResult['success']) {
                    $this->warn("   ⚠️  Error al importar asistencias: " . $attendancesResult['error']);
                    continue;
                }
                $this->line("   ✅ Asistencias importadas: {$attendancesResult['created']}");

                // Importar premios
                $prizesResult = $this->importPrizes($event, $prizesFile);
                if (!$prizesResult['success']) {
                    $this->warn("   ⚠️  Error al importar premios: " . $prizesResult['error']);
                    continue;
                }
                $this->line("   ✅ Premios importados: {$prizesResult['imported']}");

                // Crear entradas de rifa para premios públicos
                $publicPrizes = $event->prizes()
                    ->where('active', true)
                    ->where('name', '!=', 'Rifa General')
                    ->get();

                $this->line("   🎲 Creando entradas de rifa para " . $publicPrizes->count() . " premios públicos...");
                foreach ($publicPrizes as $prize) {
                    $entriesResult = $this->raffleService->createRaffleEntries($prize, 'public');
                    if ($entriesResult['success']) {
                        $this->line("      • {$prize->name}: {$entriesResult['entries_created']} entradas creadas");
                    }
                }

                // Rifar todos los premios públicos
                $this->line("   🎯 Rifando premios públicos...");
                foreach ($publicPrizes as $prize) {
                    $drawResult = $this->raffleService->drawRaffle($prize, 1, false, 'public');
                    if ($drawResult['success']) {
                        $this->line("      • {$prize->name}: " . count($drawResult['winners']) . " ganador(es)");
                    }
                }

                // Crear entradas para rifa general
                $this->line("   🎲 Creando entradas para rifa general...");
                $generalEntriesResult = $this->raffleService->createGeneralRaffleEntries($event);
                if ($generalEntriesResult['success']) {
                    $this->line("      ✅ {$generalEntriesResult['entries_created']} entradas creadas");
                }

                // Rifar 76 premios en la rifa general
                $this->line("   🎯 Rifando {$generalWinners} ganadores en rifa general...");
                $generalDrawResult = $this->raffleService->drawGeneralRaffle($event, $generalWinners, false);
                if ($generalDrawResult['success']) {
                    $this->line("      ✅ {$generalDrawResult['winners_count']} ganadores seleccionados");
                }

                // Recopilar ganadores de esta rifa
                $winners = RaffleEntry::where('event_id', $event->id)
                    ->where('status', 'won')
                    ->with(['guest', 'prize'])
                    ->get();

                foreach ($winners as $winner) {
                    $allWinners[] = [
                        'rifa_numero' => $i,
                        'event_id' => $event->id,
                        'event_name' => $event->name,
                        'winner' => $winner,
                    ];
                }

                $this->line("   ✅ Rifa #{$i} completada. Total ganadores: " . $winners->count());
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Exportar todos los ganadores
            $this->info("📤 Exportando todos los ganadores...");
            $exportPath = $this->exportAllWinners($allWinners);
            $this->info("✅ Archivo exportado: {$exportPath}");
            $this->newLine();
            $this->info("📊 Resumen:");
            $this->line("   • Total de rifas simuladas: {$count}");
            $this->line("   • Total de ganadores exportados: " . count($allWinners));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la simulación: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Crear un evento para la rifa
     */
    protected function createEvent(int $rifaNumber): Event
    {
        return Event::create([
            'user_id' => 1, // Ajustar según tu sistema
            'name' => "Rifa de Prueba #{$rifaNumber}",
            'description' => "Rifa de prueba simulada #{$rifaNumber}",
            'event_date' => Carbon::now()->addDays($rifaNumber),
            'start_time' => Carbon::now()->setTime(10, 0),
            'end_time' => Carbon::now()->setTime(18, 0),
            'location' => 'Ubicación de Prueba',
            'status' => 'active',
        ]);
    }

    /**
     * Importar guests desde CSV
     */
    protected function importGuests(Event $event, string $filePath): array
    {
        try {
            $file = new UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true
            );

            $results = $this->guestImportService->importFromCsv($file, $event);

            return [
                'success' => count($results['errors']) === 0,
                'imported' => $results['imported'],
                'errors' => $results['errors'],
                'error' => count($results['errors']) > 0 ? 'Errores en la importación' : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'imported' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Importar asistencias desde TXT
     */
    protected function importAttendances(Event $event, string $filePath): array
    {
        try {
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                return [
                    'success' => false,
                    'created' => 0,
                    'error' => 'No se pudo leer el archivo',
                ];
            }

            $employeeNumbers = collect(explode("\n", $fileContent))
                ->map(fn($line) => trim($line))
                ->filter(fn($line) => !empty($line))
                ->unique()
                ->values();

            $validGuests = Guest::where('event_id', $event->id)
                ->whereIn('numero_empleado', $employeeNumbers)
                ->get()
                ->keyBy('numero_empleado');

            $validNumbers = $employeeNumbers->intersect($validGuests->keys());
            $validGuestIds = $validGuests->pluck('id');

            $existingAttendances = Attendance::where('event_id', $event->id)
                ->whereIn('guest_id', $validGuestIds)
                ->pluck('guest_id');

            $toCreate = $validGuestIds->diff($existingAttendances);

            $created = 0;
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

            $guestsToCreate = Guest::whereIn('id', $toCreate)->get()->keyBy('id');

            foreach ($toCreate as $guestId) {
                $guest = $guestsToCreate[$guestId];
                $minutesFromStart = rand(0, max(1, $eventStartTime->diffInMinutes($eventEndTime)));
                $scannedAt = $eventStartTime->copy()->addMinutes($minutesFromStart);

                if ($scannedAt->gt(now())) {
                    $scannedAt = now()->subSeconds(rand(1, 300));
                }

                Attendance::create([
                    'event_id' => $event->id,
                    'guest_id' => $guest->id,
                    'scanned_at' => $scannedAt,
                    'scanned_by' => 'Simulación de Pruebas',
                    'scan_count' => 1,
                    'last_scanned_at' => $scannedAt,
                    'scan_metadata' => [
                        'method' => 'test_simulation',
                        'imported_from_file' => basename($filePath),
                        'imported_at' => now()->toIso8601String(),
                    ]
                ]);

                $created++;
            }

            return [
                'success' => true,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'created' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Importar premios desde CSV
     */
    protected function importPrizes(Event $event, string $filePath): array
    {
        try {
            $file = new UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true
            );

            $results = $this->prizeImportService->importFromCsv($file, $event);

            return [
                'success' => count($results['errors']) === 0,
                'imported' => $results['imported'],
                'errors' => $results['errors'],
                'error' => count($results['errors']) > 0 ? 'Errores en la importación' : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'imported' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Exportar todos los ganadores a un archivo CSV
     */
    protected function exportAllWinners(array $allWinners): string
    {
        $filename = "todas_las_rifas_ganadores_" . date('Y-m-d_His') . ".csv";
        $outputPath = storage_path('app/exports/' . $filename);

        // Crear directorio si no existe
        $exportDir = storage_path('app/exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Headers del CSV
        $headers = [
            'NumeroRifa',
            'EventoID',
            'NombreEvento',
            'TipoRifa',
            'Premio',
            'NumEmpleado',
            'NombreEmpleado',
            'Descripcion',
            'CategoriaRifa',
        ];

        $lines = [];
        $lines[] = $this->csvEscape($headers);

        // Obtener los tipos de rifa desde RaffleLog
        $eventIds = collect($allWinners)->pluck('event_id')->unique();
        $raffleLogs = RaffleLog::whereIn('event_id', $eventIds)
            ->whereIn('guest_id', collect($allWinners)->pluck('winner.guest_id'))
            ->whereIn('prize_id', collect($allWinners)->pluck('winner.prize_id'))
            ->get()
            ->keyBy(function ($log) {
                return $log->guest_id . '_' . $log->prize_id;
            });

        // Agregar datos de cada ganador
        foreach ($allWinners as $winnerData) {
            $winner = $winnerData['winner'];
            $guest = $winner->guest;
            $prize = $winner->prize;

            // Obtener tipo de rifa
            $raffleType = $this->getRaffleType($winner, $raffleLogs, $prize);

            $row = [
                $winnerData['rifa_numero'],
                $winnerData['event_id'],
                $winnerData['event_name'],
                $raffleType,
                $prize->name ?? '',
                $guest->numero_empleado ?? '',
                $guest->nombre_completo ?? '',
                $guest->descripcion ?? '',
                $guest->categoria_rifa ?? ''
            ];

            $lines[] = $this->csvEscape($row);
        }

        $csvContent = implode("\n", $lines);
        file_put_contents($outputPath, $csvContent);

        return $outputPath;
    }

    /**
     * Obtener el tipo de rifa para un ganador
     */
    protected function getRaffleType($winner, $raffleLogs, $prize): string
    {
        // Intentar obtener del RaffleLog
        $logKey = $winner->guest_id . '_' . $winner->prize_id;
        if (isset($raffleLogs[$logKey])) {
            $raffleType = $raffleLogs[$logKey]->raffle_type;
            if ($raffleType) {
                return $raffleType === 'public' ? 'Pública' : 'General';
            }
        }

        // Si no hay log, inferir del nombre del premio
        if ($prize && $prize->name === 'Rifa General') {
            return 'General';
        }

        // Por defecto, asumir que es pública
        return 'Pública';
    }

    /**
     * Escapar valores para CSV
     */
    protected function csvEscape(array $values): string
    {
        $escaped = array_map(function($value) {
            // Convertir null a string vacío
            if ($value === null) {
                return '';
            }
            
            // Si el valor contiene comas, comillas o saltos de línea, encerrarlo en comillas
            if (strpos($value, ',') !== false || 
                strpos($value, '"') !== false || 
                strpos($value, "\n") !== false ||
                strpos($value, "\r") !== false) {
                // Escapar comillas dobles duplicándolas
                $value = str_replace('"', '""', $value);
                return '"' . $value . '"';
            }
            
            return $value;
        }, $values);

        return implode(',', $escaped);
    }
}

