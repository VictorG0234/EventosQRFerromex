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
use App\Services\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class TestSingleRaffle extends Command
{
    protected $raffleService;
    protected $guestImportService;
    protected $prizeImportService;
    protected $qrCodeService;
    protected $event;
    protected $assertions = [];
    protected $failedAssertions = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:single-raffle 
                            {--guests-file=storage/app/exports/guests.csv : Ruta del archivo de guests}
                            {--attendances-file=storage/app/exports/asistencias.txt : Ruta del archivo de asistencias}
                            {--prizes-file=storage/app/exports/premios.csv : Ruta del archivo de premios}
                            {--general-prizes-file= : CSV de premios de rifa general; por defecto se usa el archivo de premios}
                            {--general-winners=76 : Número de ganadores en la rifa general}
                            {--seed= : Seed fijo para aleatoriedad (opcional, para tests determinísticos)}
                            {--additional-users=20 : Número de usuarios adicionales a crear}
                            {--attendance-percentage=75 : Porcentaje de asistencia para usuarios adicionales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test completo de una sola rifa con todos los asserts necesarios';

    public function __construct(
        RaffleService $raffleService,
        GuestImportService $guestImportService,
        PrizeImportService $prizeImportService,
        QrCodeService $qrCodeService
    ) {
        parent::__construct();
        $this->raffleService = $raffleService;
        $this->guestImportService = $guestImportService;
        $this->prizeImportService = $prizeImportService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        // Establecer seed fijo si se proporciona (para tests determinísticos)
        $seed = $this->option('seed');
        if ($seed !== null && $seed !== '') {
            mt_srand((int) $seed);
            srand((int) $seed);
            $this->info("🎲 Iniciando test completo de una sola rifa (Seed: {$seed})");
        } else {
            $this->info("🎲 Iniciando test completo de una sola rifa");
        }
        $this->newLine();

        $guestsFile = $this->option('guests-file');
        $attendancesFile = $this->option('attendances-file');
        $prizesFile = $this->option('prizes-file');
        $generalPrizesFile = $this->option('general-prizes-file') ?: $prizesFile;
        $generalWinners = (int) $this->option('general-winners');

        // Verificar archivos
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

        if (!file_exists($generalPrizesFile)) {
            $this->error("❌ No se encontró el archivo de premios generales: {$generalPrizesFile}");
            return 1;
        }

        $this->info("📁 Archivos encontrados:");
        $this->line("   • Guests: {$guestsFile}");
        $this->line("   • Asistencias: {$attendancesFile}");
        $this->line("   • Premios: {$prizesFile}");
        $this->line("   • Premios generales: {$generalPrizesFile}");
        $this->newLine();

        try {
            DB::beginTransaction();

            // TEST 1: Crear evento
            $this->info("📋 TEST 1: Crear evento");
            $this->event = $this->createEvent();
            $this->assert("El evento se puede crear correctamente", $this->event !== null && $this->event->id > 0);
            $this->line("   ✅ Evento creado: {$this->event->name} (ID: {$this->event->id})");
            $this->newLine();

            // TEST 2: Importar guests desde CSV
            $this->info("📋 TEST 2: Importar usuarios desde CSV");
            $guestsResult = $this->importGuests($guestsFile);
            $this->assert("Se pueden cargar usuarios al evento usando un csv", 
                $guestsResult['success'] && $guestsResult['imported'] > 0);
            $this->line("   ✅ Guests importados: {$guestsResult['imported']}");
            $this->newLine();

            // TEST 3: Agregar usuario manualmente
            $this->info("📋 TEST 3: Agregar usuario de forma manual");
            $manualGuest = $this->createManualGuest();
            $this->assert("Se pueden agregar usuarios de forma manual", 
                $manualGuest !== null && $manualGuest->id > 0);
            $this->line("   ✅ Usuario manual creado: {$manualGuest->nombre_completo} (ID: {$manualGuest->id})");
            $this->newLine();

            // TEST 4: Marcar asistencias
            $attendancePercentage = (int) $this->option('attendance-percentage');
            
            if ($attendancePercentage > 0) {
                // Si se proporciona porcentaje, tiene prioridad sobre el archivo
                $this->info("📋 TEST 4: Marcar asistencia por porcentaje ({$attendancePercentage}%)");
                $attendancesResult = $this->markAttendanceByPercentage($attendancePercentage);
                $this->assert("Se marca asistencia a usuarios usando el porcentaje designado", 
                    $attendancesResult['success'] && $attendancesResult['created'] > 0);
                $this->line("   ✅ Asistencias marcadas por porcentaje: {$attendancesResult['created']} de {$attendancesResult['total']} ({$attendancePercentage}%)");
            } else {
                // Si no hay porcentaje, usar el archivo de asistencias
                $this->info("📋 TEST 4: Marcar asistencia desde archivo");
                $attendancesResult = $this->importAttendances($attendancesFile);
                $this->assert("Se marca asistencia a usuarios usando el script que hace eso leyendo el archivo asistencias", 
                    $attendancesResult['success'] && $attendancesResult['created'] > 0);
                $this->line("   ✅ Asistencias importadas: {$attendancesResult['created']}");
            }
            $this->newLine();

            // TEST 4B: Agregar usuarios adicionales con empresas INV, GMXT e IMEX
            $this->info("📋 TEST 4B: Agregar usuarios adicionales con empresas INV, GMXT e IMEX");
            $additionalUsersCount = (int) $this->option('additional-users');
            // Si hay porcentaje, aplicarlo también a usuarios adicionales, si no, usar 75% por defecto
            $additionalUsersPercentage = $attendancePercentage > 0 ? $attendancePercentage : 75;
            $additionalUsersResult = $this->createAdditionalUsers($additionalUsersCount, $additionalUsersPercentage);
            $this->assert("Se pueden crear usuarios adicionales con empresas INV, GMXT e IMEX con valores random", 
                $additionalUsersResult['success'] && $additionalUsersResult['created'] === $additionalUsersCount);
            $this->line("   ✅ Usuarios adicionales creados: {$additionalUsersResult['created']}");
            $this->line("   ✅ Asistencias marcadas: {$additionalUsersResult['attendances_marked']} de {$additionalUsersResult['created']} ({$attendancePercentage}%)");
            $this->newLine();

            // Verificar que los guests tienen QR codes
            $this->info("📋 TEST ADICIONAL: Verificar códigos QR");
            $guestsWithQR = Guest::where('event_id', $this->event->id)
                ->whereNotNull('qr_code')
                ->count();
            $totalGuests = Guest::where('event_id', $this->event->id)->count();
            $this->assert("Los invitados tienen códigos QR generados", 
                $guestsWithQR === $totalGuests && $totalGuests > 0);
            $this->line("   ✅ {$guestsWithQR} de {$totalGuests} invitados tienen QR");
            $this->newLine();

            // TEST 5: Crear premio manualmente (PRIMERO)
            $this->info("📋 TEST 5: Crear regalo de forma manual");
            $manualPrize = $this->createManualPrize();
            $this->assert("Se puede crear un regalo de forma manual", 
                $manualPrize !== null && $manualPrize->id > 0);
            $this->line("   ✅ Premio manual creado: {$manualPrize->name} (ID: {$manualPrize->id})");
            $this->newLine();

            // TEST 6: Importar premios desde CSV (DESPUÉS)
            $this->info("📋 TEST 6: Subir regalos mediante CSV");
            $prizesResult = $this->importPrizes($prizesFile);
            $this->assert("Se pueden subir los regalos mediante un csv de forma automatica", 
                $prizesResult['success'] && $prizesResult['imported'] > 0);
            $this->line("   ✅ Premios importados: {$prizesResult['imported']}");
            $this->newLine();

            // Obtener premios públicos (excluyendo Rifa General)
            $publicPrizes = $this->event->prizes()
                ->where('active', true)
                ->where('name', '!=', 'Rifa General')
                ->get();

            // TEST 7: Crear entradas y rifar premios públicos
            $this->info("📋 TEST 7: Ejecutar rifa pública para todos los regalos");
            $publicRaffleSuccess = true;
            $publicWinners = [];
            
            foreach ($publicPrizes as $prize) {
                // Crear entradas
                $entriesResult = $this->raffleService->createRaffleEntries($prize, 'public');
                if (!$entriesResult['success']) {
                    $publicRaffleSuccess = false;
                    $this->warn("      ⚠️  Error al crear entradas para {$prize->name}");
                    continue;
                }

                // Rifar
                $drawResult = $this->raffleService->drawRaffle($prize, 1, false, 'public');
                if ($drawResult['success']) {
                    $publicWinners = array_merge($publicWinners, $drawResult['winners']);
                    $this->line("      • {$prize->name}: " . count($drawResult['winners']) . " ganador(es)");
                } else {
                    $publicRaffleSuccess = false;
                    $this->warn("      ⚠️  Error al rifar {$prize->name}: " . ($drawResult['error'] ?? 'Error desconocido'));
                }
            }

            $this->assert("La rifa publica se ejecuta de manera exitosa para todos los regalos", 
                $publicRaffleSuccess && count($publicWinners) > 0);
            $this->line("   ✅ Rifa pública completada. Total ganadores: " . count($publicWinners));
            $this->newLine();

            // TEST 8: Crear entradas y rifar rifa general
            $this->info("📋 TEST 8: Ejecutar rifa general");
            $generalPrizesFileUpload = new UploadedFile($generalPrizesFile, basename($generalPrizesFile), null, null, true);
            $importedGeneralPrizes = $this->prizeImportService->importGeneralFromCsv($generalPrizesFileUpload, $this->event);
            $this->line("   • Premios generales importados: {$importedGeneralPrizes}");
            $generalEntriesResult = $this->raffleService->createGeneralRaffleEntries($this->event);
            $generalDrawResult = $this->raffleService->drawGeneralRaffle($this->event, $generalWinners, false);
            if (!$generalDrawResult['success']) {
                throw new \RuntimeException('No se pudo realizar la rifa general: ' . $generalDrawResult['error']);
            }
            
            $this->assert("La rifa general se ejecuta de forma exitosa para todos los usuarios", 
                $generalDrawResult['success'] && $generalDrawResult['winners_count'] > 0);
            $this->line("   ✅ Rifa general completada. Ganadores: {$generalDrawResult['winners_count']}");
            $this->newLine();

            // TEST 9: Verificar descripciones prohibidas en ganadores de rifa pública
            $this->info("📋 TEST 9: Verificar descripciones prohibidas en ganadores de rifa pública");
            $excludedDescriptions = ['Ganadores previos', 'Nuevo ingreso', 'Directores'];
            $publicWinnersEntries = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', '!=', 'Rifa General');
                })
                ->with('guest')
                ->get();

            $hasProhibitedDescriptions = false;
            $hasEmptyDescriptions = false;
            foreach ($publicWinnersEntries as $entry) {
                $descripcion = strtolower(trim($entry->guest->descripcion ?? ''));
                if (empty($descripcion)) {
                    $hasEmptyDescriptions = true;
                }
                foreach ($excludedDescriptions as $excluded) {
                    if ($descripcion === strtolower(trim($excluded))) {
                        $hasProhibitedDescriptions = true;
                        break 2;
                    }
                }
            }

            $this->assert("Los ganadores de la rifa publica No tienen descripciones prohibidas o vacias", 
                !$hasProhibitedDescriptions && !$hasEmptyDescriptions);
            $this->line("   ✅ Ningún ganador tiene descripciones prohibidas o vacías");
            $this->newLine();

            // TEST 10: Verificar categorías prohibidas en ganadores de rifa pública
            $this->info("📋 TEST 10: Verificar categorías prohibidas en ganadores de rifa pública");
            $excludedCategories = ['No Participa'];
            $hasProhibitedCategories = false;
            
            foreach ($publicWinnersEntries as $entry) {
                $categoria = strtolower(trim($entry->guest->categoria_rifa ?? ''));
                foreach ($excludedCategories as $excluded) {
                    if ($categoria === strtolower(trim($excluded))) {
                        $hasProhibitedCategories = true;
                        break 2;
                    }
                }
            }

            $this->assert("Los ganadores de la rifa publica no tienen categorias prohibidas", 
                !$hasProhibitedCategories);
            $this->line("   ✅ Ningún ganador tiene categorías prohibidas");
            $this->newLine();

            // TEST 11: Verificar que no hay ganadores repetidos
            $this->info("📋 TEST 11: Verificar que no hay ganadores repetidos");
            
            // Verificar duplicados en rifa pública (un guest no puede ganar múltiples premios públicos)
            $publicWinners = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', '!=', 'Rifa General');
                })
                ->with('guest')
                ->get();

            $publicGuestIds = $publicWinners->pluck('guest_id')->toArray();
            $uniquePublicGuestIds = array_unique($publicGuestIds);
            $hasPublicDuplicates = count($publicGuestIds) !== count($uniquePublicGuestIds);

            if ($hasPublicDuplicates) {
                $duplicates = array_diff_assoc($publicGuestIds, $uniquePublicGuestIds);
                $duplicateIds = array_unique($duplicates);
                $this->warn("      ⚠️  Duplicados encontrados en rifa pública: " . implode(', ', $duplicateIds));
            }

            // Verificar duplicados en rifa general (un guest no puede ganar múltiples veces en rifa general)
            $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($this->event);
            $generalWinners = RaffleEntry::where('event_id', $this->event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'won')
                ->with('guest')
                ->get();

            $generalGuestIds = $generalWinners->pluck('guest_id')->toArray();
            $uniqueGeneralGuestIds = array_unique($generalGuestIds);
            $hasGeneralDuplicates = count($generalGuestIds) !== count($uniqueGeneralGuestIds);

            if ($hasGeneralDuplicates) {
                $duplicates = array_diff_assoc($generalGuestIds, $uniqueGeneralGuestIds);
                $duplicateIds = array_unique($duplicates);
                $this->warn("      ⚠️  Duplicados encontrados en rifa general: " . implode(', ', $duplicateIds));
            }

            $this->assert("No hay ganadores repetidos en rifa pública (un guest no puede ganar múltiples premios públicos)", !$hasPublicDuplicates);
            $this->assert("No hay ganadores repetidos en rifa general (un guest no puede ganar múltiples veces)", !$hasGeneralDuplicates);
            
            if (!$hasPublicDuplicates && !$hasGeneralDuplicates) {
                $this->line("   ✅ No hay ganadores duplicados en rifa pública. Total ganadores únicos: " . count($uniquePublicGuestIds));
                $this->line("   ✅ No hay ganadores duplicados en rifa general. Total ganadores únicos: " . count($uniqueGeneralGuestIds));
            }
            $this->newLine();

            // TEST 12: Verificar descripciones permitidas en ganadores de rifa general
            $this->info("📋 TEST 12: Verificar descripciones permitidas en ganadores de rifa general");
            $allowedDescriptions = ['General', 'Subdirectores', 'IMEX'];
            $generalWinnersEntries = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', 'Rifa General');
                })
                ->with('guest')
                ->get();

            $hasInvalidDescriptions = false;
            foreach ($generalWinnersEntries as $entry) {
                $descripcion = strtolower(trim($entry->guest->descripcion ?? ''));
                $isAllowed = false;
                foreach ($allowedDescriptions as $allowed) {
                    if ($descripcion === strtolower(trim($allowed))) {
                        $isAllowed = true;
                        break;
                    }
                }
                if (!$isAllowed && !empty($descripcion)) {
                    $hasInvalidDescriptions = true;
                    break;
                }
            }

            $this->assert("Los ganadores de la rifa general solo tienen las descripciones permitidas", 
                !$hasInvalidDescriptions);
            $this->line("   ✅ Todos los ganadores de rifa general tienen descripciones permitidas");
            $this->newLine();

            // TESTS ADICIONALES

            // TEST ADICIONAL 1: Verificar que los invitados con descripciones prohibidas no participan en rifa pública
            $this->info("📋 TEST ADICIONAL 1: Invitados con descripciones prohibidas no participan en rifa pública");
            $prohibitedGuests = Guest::where('event_id', $this->event->id)
                ->whereIn('descripcion', $excludedDescriptions)
                ->get();
            
            $prohibitedInPublicRaffle = true;
            foreach ($prohibitedGuests as $guest) {
                $hasPublicEntry = RaffleEntry::where('guest_id', $guest->id)
                    ->whereHas('prize', function ($q) {
                        $q->where('name', '!=', 'Rifa General');
                    })
                    ->exists();
                if ($hasPublicEntry) {
                    $prohibitedInPublicRaffle = false;
                    break;
                }
            }
            $this->assert("Los invitados con descripciones prohibidas no participan en rifa pública", 
                $prohibitedInPublicRaffle);
            $this->line("   ✅ Invitados con descripciones prohibidas no participan en rifa pública");
            $this->newLine();

            // TEST ADICIONAL 2: Verificar que los invitados INV no participan en ninguna rifa
            $this->info("📋 TEST ADICIONAL 2: Invitados INV no participan en ninguna rifa");
            $invGuests = Guest::where('event_id', $this->event->id)
                ->whereRaw('UPPER(TRIM(compania)) = ?', ['INV'])
                ->get();
            
            $invNotInAnyRaffle = true;
            foreach ($invGuests as $guest) {
                $hasAnyEntry = RaffleEntry::where('guest_id', $guest->id)->exists();
                if ($hasAnyEntry) {
                    $invNotInAnyRaffle = false;
                    break;
                }
            }
            $this->assert("Los invitados INV no participan en ninguna rifa (ni pública ni general)", $invNotInAnyRaffle);
            $this->line("   ✅ Invitados INV no participan en ninguna rifa");
            $this->newLine();

            // TEST ADICIONAL 3: Verificar que hay exactamente 1 ganador IMEX en rifa pública
            $this->info("📋 TEST ADICIONAL 3: Verificar ganador IMEX en rifa pública");
            $imexWinners = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', '!=', 'Rifa General');
                })
                ->whereHas('guest', function ($q) {
                    $q->whereRaw('UPPER(TRIM(compania)) = ?', ['IMEX']);
                })
                ->count();
            
            $this->assert("Hay exactamente 1 ganador IMEX en rifa pública", $imexWinners === 1);
            $this->line("   ✅ Ganadores IMEX en rifa pública: {$imexWinners}");
            $this->newLine();

            // TEST ADICIONAL 3B: Verificar que hay exactamente 2 ganadores IMEX en rifa general
            $this->info("📋 TEST ADICIONAL 3B: Verificar ganadores IMEX en rifa general");
            $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($this->event);
            $imexGeneralWinners = RaffleEntry::where('event_id', $this->event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'won')
                ->whereHas('guest', function ($q) {
                    $q->whereRaw('UPPER(TRIM(compania)) = ?', ['IMEX']);
                })
                ->count();
            
            $this->assert("Hay exactamente 2 ganadores IMEX en rifa general", $imexGeneralWinners === 2);
            $this->line("   ✅ Ganadores IMEX en rifa general: {$imexGeneralWinners}");
            $this->newLine();

            // TEST ADICIONAL 4: Verificar que ningún guest gana en ambas rifas (pública Y general)
            $this->info("📋 TEST ADICIONAL 4: Verificar que ningún guest gana en ambas rifas");
            $publicWinnerGuestIds = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', '!=', 'Rifa General');
                })
                ->pluck('guest_id')
                ->toArray();

            $generalWinnerGuestIds = RaffleEntry::where('event_id', $this->event->id)
                ->where('status', 'won')
                ->whereHas('prize', function ($q) {
                    $q->where('name', 'Rifa General');
                })
                ->pluck('guest_id')
                ->toArray();

            $overlap = array_intersect($publicWinnerGuestIds, $generalWinnerGuestIds);
            
            if (!empty($overlap)) {
                $duplicateGuests = Guest::whereIn('id', $overlap)->get();
                $this->warn("      ⚠️  Guests que ganaron en ambas rifas: " . $duplicateGuests->pluck('nombre_completo')->implode(', '));
            }
            
            $this->assert("Ningún guest gana en ambas rifas (pública Y general)", 
                empty($overlap));
            $this->line("   ✅ No hay guests que hayan ganado en ambas rifas. Total ganadores únicos en pública: " . count($publicWinnerGuestIds) . ", en general: " . count($generalWinnerGuestIds));
            $this->newLine();

            // TEST ADICIONAL 5: Verificar que el stock se actualiza correctamente
            $this->info("📋 TEST ADICIONAL 5: Verificar actualización de stock");
            $allPrizes = $this->event->prizes()
                ->where('active', true)
                ->where('name', '!=', 'Rifa General')
                ->get();
            $stockCorrect = true;
            
            foreach ($allPrizes as $prize) {
                $winnersCount = RaffleEntry::where('prize_id', $prize->id)
                    ->where('status', 'won')
                    ->count();
                
                // El stock debería ser 0 si hay ganadores (cada premio tiene stock=1 inicialmente)
                if ($winnersCount > 0 && $prize->stock > 0) {
                    $stockCorrect = false;
                    break;
                }
            }
            
            $this->assert("El stock de los premios se actualiza correctamente después de rifar", 
                $stockCorrect);
            $this->line("   ✅ Stock actualizado correctamente");
            $this->newLine();

            // TEST ADICIONAL 6: Verificar que los invitados con categoría "No Participa" no participan
            $this->info("📋 TEST ADICIONAL 6: Invitados con categoría 'No Participa' no participan");
            $noParticipaGuests = Guest::where('event_id', $this->event->id)
                ->whereRaw('UPPER(TRIM(categoria_rifa)) = ?', ['NO PARTICIPA'])
                ->get();
            
            $noParticipaNotInRaffle = true;
            foreach ($noParticipaGuests as $guest) {
                $hasEntry = RaffleEntry::where('guest_id', $guest->id)->exists();
                if ($hasEntry) {
                    $noParticipaNotInRaffle = false;
                    break;
                }
            }
            $this->assert("Los invitados con categoría 'No Participa' no participan en rifas", 
                $noParticipaNotInRaffle);
            $this->line("   ✅ Invitados con categoría 'No Participa' no participan");
            $this->newLine();

            // TEST ADICIONAL 7: Verificar que los invitados con descripción "Subdirectores" no ganan "Automovil"
            $this->info("📋 TEST ADICIONAL 7: Subdirectores no ganan premio Automovil");
            $automovilPrize = Prize::where('event_id', $this->event->id)
                ->whereRaw('LOWER(name) = ?', ['automovil'])
                ->first();
            
            $subdirectoresNotWinAutomovil = true;
            if ($automovilPrize) {
                $automovilWinners = RaffleEntry::where('prize_id', $automovilPrize->id)
                    ->where('status', 'won')
                    ->whereHas('guest', function ($q) {
                        $q->whereRaw('LOWER(TRIM(descripcion)) = ?', ['subdirectores']);
                    })
                    ->exists();
                
                $subdirectoresNotWinAutomovil = !$automovilWinners;
            }
            $this->assert("Los invitados con descripción 'Subdirectores' no ganan premio 'Automovil'", 
                $subdirectoresNotWinAutomovil);
            $this->line("   ✅ Subdirectores no ganan Automovil");
            $this->newLine();

            DB::commit();

            // Mostrar resumen
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->newLine();
            $this->info("📊 RESUMEN DE TESTS");
            $this->newLine();
            $this->table(
                ['Test', 'Estado'],
                collect($this->assertions)->map(function ($assertion) {
                    return [
                        $assertion['name'],
                        $assertion['passed'] ? '✅ PASÓ' : '❌ FALLÓ'
                    ];
                })->toArray()
            );

            $this->newLine();
            $passed = collect($this->assertions)->where('passed', true)->count();
            $total = count($this->assertions);
            $this->info("✅ Tests pasados: {$passed} / {$total}");
            
            if (count($this->failedAssertions) > 0) {
                $this->error("❌ Tests fallidos: " . count($this->failedAssertions));
                foreach ($this->failedAssertions as $failed) {
                    $this->error("   • {$failed}");
                }
            }
            
            $this->newLine();
            $this->info("⏱️  Tiempo total: {$duration} segundos");
            $this->newLine();
            $this->info("📅 Evento de prueba: {$this->event->name} (ID: {$this->event->id})");
            $this->info("   • Total invitados: " . Guest::where('event_id', $this->event->id)->count());
            $this->info("   • Total asistencias: " . Attendance::where('event_id', $this->event->id)->count());
            $this->info("   • Total premios: " . Prize::where('event_id', $this->event->id)->count());
            $this->info("   • Total ganadores: " . RaffleEntry::where('event_id', $this->event->id)->where('status', 'won')->count());

            // Exportar ganadores a CSV
            $this->newLine();
            $this->info("📤 Exportando ganadores a CSV...");
            $exportPath = $this->exportWinners();
            $this->info("✅ Archivo CSV generado: {$exportPath}");
            $this->newLine();

            return count($this->failedAssertions) === 0 ? 0 : 1;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error durante el test: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Helper para hacer asserts
     */
    protected function assert(string $name, bool $condition, ?string $message = null)
    {
        $this->assertions[] = [
            'name' => $name,
            'passed' => $condition,
            'message' => $message
        ];

        if (!$condition) {
            $this->failedAssertions[] = $name . ($message ? ": {$message}" : '');
        }
    }

    /**
     * Crear un evento para la rifa
     */
    protected function createEvent(): Event
    {
        return Event::create([
            'user_id' => 1,
            'name' => "Test de Rifa Única - " . date('Y-m-d H:i:s'),
            'description' => "Test completo de una sola rifa con todos los asserts",
            'event_date' => Carbon::now()->addDays(1),
            'start_time' => Carbon::now()->setTime(10, 0),
            'end_time' => Carbon::now()->setTime(18, 0),
            'location' => 'Ubicación de Prueba',
            'status' => 'active',
        ]);
    }

    /**
     * Importar guests desde CSV
     */
    protected function importGuests(string $filePath): array
    {
        try {
            $file = new UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true
            );

            $results = $this->guestImportService->importFromCsv($file, $this->event);

            return [
                'success' => count($results['errors']) === 0,
                'imported' => $results['imported'],
                'errors' => $results['errors'],
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
     * Crear un guest manualmente
     */
    protected function createManualGuest(): Guest
    {
        // Crear el invitado con compañía INV y categoría "No Participa"
        // Este usuario NO debe participar en ninguna rifa (ni pública ni general)
        $guest = Guest::create([
            'event_id' => $this->event->id,
            'compania' => 'INV',
            'numero_empleado' => 'MANUAL_1',
            'nombre_completo' => 'Usuario Manual de Prueba',
            'correo' => 'manual.test@ferromex.com',
            'puesto' => 'Tester',
            'nivel_de_puesto' => 'Senior',
            'localidad' => 'Ciudad de Prueba',
            'fecha_alta' => Carbon::now()->subYears(2),
            'descripcion' => 'General',
            'categoria_rifa' => 'No Participa',
        ]);

        // Generar código QR completo (imagen) - igual que cuando se importa desde CSV
        $this->qrCodeService->generateQrCode($guest);

        return $guest->fresh();
    }

    /**
     * Marcar asistencias por porcentaje para todos los usuarios del evento
     */
    protected function markAttendanceByPercentage(int $percentage): array
    {
        try {
            // Obtener todos los usuarios del evento que aún no tienen asistencia
            $allGuests = Guest::where('event_id', $this->event->id)
                ->whereDoesntHave('attendance')
                ->get();

            $totalGuests = $allGuests->count();
            
            if ($totalGuests === 0) {
                return [
                    'success' => true,
                    'created' => 0,
                    'total' => 0,
                ];
            }

            // Calcular cuántos usuarios deben tener asistencia
            $usersToMark = (int) round($totalGuests * ($percentage / 100));
            
            // Mezclar aleatoriamente y tomar los primeros N
            $guestsToMark = $allGuests->shuffle()->take($usersToMark);

            $eventDate = $this->event->event_date;
            $startTime = $this->event->start_time;
            $endTime = $this->event->end_time;

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

            $created = 0;
            foreach ($guestsToMark as $guest) {
                $minutesFromStart = rand(0, max(1, $eventStartTime->diffInMinutes($eventEndTime)));
                $scannedAt = $eventStartTime->copy()->addMinutes($minutesFromStart);

                if ($scannedAt->gt(now())) {
                    $scannedAt = now()->subSeconds(rand(1, 300));
                }

                Attendance::create([
                    'event_id' => $this->event->id,
                    'guest_id' => $guest->id,
                    'scanned_at' => $scannedAt,
                    'scanned_by' => 'Test Script - Porcentaje',
                    'scan_count' => 1,
                    'last_scanned_at' => $scannedAt,
                    'scan_metadata' => [
                        'method' => 'test_script_percentage',
                        'percentage' => $percentage,
                        'created_at' => now()->toIso8601String(),
                    ]
                ]);

                $created++;
            }

            return [
                'success' => true,
                'created' => $created,
                'total' => $totalGuests,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'created' => 0,
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Importar asistencias desde TXT
     */
    protected function importAttendances(string $filePath): array
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

            $validGuests = Guest::where('event_id', $this->event->id)
                ->whereIn('numero_empleado', $employeeNumbers)
                ->get()
                ->keyBy('numero_empleado');

            $validNumbers = $employeeNumbers->intersect($validGuests->keys());
            $validGuestIds = $validGuests->pluck('id');

            $existingAttendances = Attendance::where('event_id', $this->event->id)
                ->whereIn('guest_id', $validGuestIds)
                ->pluck('guest_id');

            $toCreate = $validGuestIds->diff($existingAttendances);

            $created = 0;
            $eventDate = $this->event->event_date;
            $startTime = $this->event->start_time;
            $endTime = $this->event->end_time;

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
                    'event_id' => $this->event->id,
                    'guest_id' => $guest->id,
                    'scanned_at' => $scannedAt,
                    'scanned_by' => 'Test Script',
                    'scan_count' => 1,
                    'last_scanned_at' => $scannedAt,
                    'scan_metadata' => [
                        'method' => 'test_script',
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
    protected function importPrizes(string $filePath): array
    {
        try {
            $file = new UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true
            );

            $results = $this->prizeImportService->importFromCsv($file, $this->event);

            return [
                'success' => count($results['errors']) === 0,
                'imported' => $results['imported'],
                'errors' => $results['errors'],
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
     * Crear un premio manualmente
     */
    protected function createManualPrize(): Prize
    {
        return Prize::create([
            'event_id' => $this->event->id,
            'name' => 'Premio Manual de Prueba',
            'description' => 'Premio creado manualmente para testing',
            'category' => 'Prueba',
            'stock' => 1,
            'value' => 1000.00,
            'active' => true,
        ]);
    }

    /**
     * Crear usuarios adicionales con empresas INV, GMXT e IMEX
     * con valores random de descripción y categoría_rifa
     */
    protected function createAdditionalUsers(int $count, int $attendancePercentage): array
    {
        try {
            // Valores posibles para descripción
            $descripciones = [
                'General',
                'Subdirectores',
                'Directores',
                'Ganadores previos',
                'Nuevo ingreso',
                'No Participa',
                'IMEX',
            ];

            // Valores posibles para categoría_rifa
            $categoriasRifa = [
                'Premium',
                'Standard',
                'VIP',
                'IMEX',
                'No Participa',
                'Básica',
                'Especial',
            ];

            // Empresas a distribuir
            $empresas = ['INV', 'GMXT', 'IMEX'];
            
            $created = 0;
            $attendancesMarked = 0;
            $eventDate = $this->event->event_date;
            $startTime = $this->event->start_time;

            if ($eventDate instanceof Carbon && $startTime instanceof Carbon) {
                $eventStartTime = Carbon::parse($eventDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
            } else {
                $eventStartTime = now()->subHours(2);
            }

            // Calcular cuántos usuarios deben tener asistencia
            $usersToMarkAttendance = (int) round($count * ($attendancePercentage / 100));

            for ($i = 0; $i < $count; $i++) {
                // Distribuir empresas de forma balanceada
                $empresa = $empresas[$i % count($empresas)];
                
                // Valores random para descripción y categoría
                $descripcion = $descripciones[array_rand($descripciones)];
                $categoriaRifa = $categoriasRifa[array_rand($categoriasRifa)];

                // Crear el usuario
                $guest = Guest::create([
                    'event_id' => $this->event->id,
                    'compania' => $empresa,
                    'numero_empleado' => 'ADD_' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'nombre_completo' => "Usuario Adicional " . ($i + 1) . " - {$empresa}",
                    'correo' => "usuario.adicional." . ($i + 1) . "@test.com",
                    'puesto' => 'Empleado de Prueba',
                    'nivel_de_puesto' => 'Operativo',
                    'localidad' => 'Ciudad de Prueba',
                    'fecha_alta' => Carbon::now()->subYears(rand(1, 5)),
                    'descripcion' => $descripcion,
                    'categoria_rifa' => $categoriaRifa,
                ]);

                // Generar código QR
                $this->qrCodeService->generateQrCode($guest);

                $created++;

                // Marcar asistencia según el porcentaje designado
                if ($attendancesMarked < $usersToMarkAttendance) {
                    $minutesFromStart = rand(0, 240); // Dentro de 4 horas del evento
                    $scannedAt = $eventStartTime->copy()->addMinutes($minutesFromStart);

                    if ($scannedAt->gt(now())) {
                        $scannedAt = now()->subSeconds(rand(1, 300));
                    }

                    Attendance::create([
                        'event_id' => $this->event->id,
                        'guest_id' => $guest->id,
                        'scanned_at' => $scannedAt,
                        'scanned_by' => 'Test Script - Usuarios Adicionales',
                        'scan_count' => 1,
                        'last_scanned_at' => $scannedAt,
                        'scan_metadata' => [
                            'method' => 'test_script_additional_users',
                            'created_at' => now()->toIso8601String(),
                        ]
                    ]);

                    $attendancesMarked++;
                }
            }

            return [
                'success' => true,
                'created' => $created,
                'attendances_marked' => $attendancesMarked,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'created' => 0,
                'attendances_marked' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Exportar ganadores a CSV
     */
    protected function exportWinners(): string
    {
        $eventName = str_replace([' ', ':', '/'], ['_', '-', '-'], $this->event->name);
        $filename = "ganadores_{$eventName}_" . date('Y-m-d_His') . ".csv";
        $outputPath = storage_path('app/exports/' . $filename);

        // Crear directorio si no existe
        $exportDir = storage_path('app/exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Headers del CSV
        $headers = [
            'EventoID',
            'NombreEvento',
            'TipoRifa',
            'Premio',
            'NumEmpleado',
            'NombreEmpleado',
            'Compañia',
            'Descripcion',
            'CategoriaRifa',
            'FechaSorteo',
        ];

        $lines = [];
        $lines[] = $this->csvEscape($headers);

        // Obtener todos los ganadores
        $winners = RaffleEntry::where('event_id', $this->event->id)
            ->where('status', 'won')
            ->with(['guest', 'prize'])
            ->get();

        // Obtener los tipos de rifa desde RaffleLog
        $raffleLogs = RaffleLog::where('event_id', $this->event->id)
            ->whereIn('guest_id', $winners->pluck('guest_id'))
            ->whereIn('prize_id', $winners->pluck('prize_id'))
            ->get()
            ->keyBy(function ($log) {
                return $log->guest_id . '_' . $log->prize_id;
            });

        // Agregar datos de cada ganador
        foreach ($winners as $winner) {
            $guest = $winner->guest;
            $prize = $winner->prize;

            // Obtener tipo de rifa
            $raffleType = $this->getRaffleType($winner, $raffleLogs, $prize);

            $row = [
                $this->event->id,
                $this->event->name,
                $raffleType,
                $prize->name ?? '',
                $guest->numero_empleado ?? '',
                $guest->nombre_completo ?? '',
                $guest->compania ?? '',
                $guest->descripcion ?? '',
                $guest->categoria_rifa ?? '',
                $winner->drawn_at ? $winner->drawn_at->format('Y-m-d H:i:s') : '',
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
            
            // Convertir a string
            $value = (string) $value;
            
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

