<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\RaffleEntry;
use App\Models\RaffleLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetEventRaffle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raffle:reset 
                            {event? : ID del evento a resetear}
                            {--all : Resetear todos los eventos}
                            {--force : No pedir confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetea todas las rifas de un evento (elimina entradas, restaura stock y limpia logs)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event');
        $resetAll = $this->option('all');
        $force = $this->option('force');

        if (!$eventId && !$resetAll) {
            // Mostrar lista de eventos disponibles
            $events = Event::orderBy('created_at', 'desc')->get();
            
            if ($events->isEmpty()) {
                $this->error('No hay eventos en el sistema.');
                return 1;
            }

            $this->info('Eventos disponibles:');
            $this->table(
                ['ID', 'Nombre', 'Fecha', 'Entradas', 'Ganadores'],
                $events->map(function ($event) {
                    $totalEntries = $event->raffleEntries()->count();
                    $winners = $event->raffleEntries()->where('status', 'won')->count();
                    return [
                        $event->id,
                        $event->name,
                        $event->event_date?->format('d/m/Y'),
                        $totalEntries,
                        $winners
                    ];
                })
            );

            $eventId = $this->ask('Ingresa el ID del evento a resetear (o "all" para todos)');
            
            if (strtolower($eventId) === 'all') {
                $resetAll = true;
            }
        }

        if ($resetAll) {
            return $this->resetAllEvents($force);
        }

        // Validar que el evento existe
        $event = Event::find($eventId);
        if (!$event) {
            $this->error("El evento con ID {$eventId} no existe.");
            return 1;
        }

        return $this->resetEvent($event, $force);
    }

    /**
     * Resetear un evento específico
     */
    protected function resetEvent(Event $event, bool $force = false): int
    {
        // Obtener estadísticas antes del reset
        $totalEntries = $event->raffleEntries()->count();
        $totalWinners = $event->raffleEntries()->where('status', 'won')->count();
        $totalPending = $event->raffleEntries()->where('status', 'pending')->count();
        $totalLost = $event->raffleEntries()->where('status', 'lost')->count();
        $totalLogs = RaffleLog::where('event_id', $event->id)->count();

        // Mostrar información
        $this->warn("═══════════════════════════════════════════════════════");
        $this->warn("  RESET DE RIFA - EVENTO: {$event->name}");
        $this->warn("═══════════════════════════════════════════════════════");
        $this->line('');
        $this->info("Estadísticas actuales:");
        $this->line("  • Total de entradas: {$totalEntries}");
        $this->line("    - Ganadores: {$totalWinners}");
        $this->line("    - Pendientes: {$totalPending}");
        $this->line("    - Perdedores: {$totalLost}");
        $this->line("  • Logs de rifa: {$totalLogs}");
        $this->line('');

        if (!$force) {
            if (!$this->confirm('¿Estás seguro de que deseas resetear todas las rifas de este evento?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // 1. Restaurar stock de premios
            $this->info('Restaurando stock de premios...');
            foreach ($event->prizes as $prize) {
                $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
                if ($winnersCount > 0) {
                    $prize->increment('stock', $winnersCount);
                    $this->line("  ✓ Premio '{$prize->name}': +{$winnersCount} stock");
                }
            }

            // 2. Eliminar todas las entradas de rifa
            $this->info('Eliminando entradas de rifa...');
            $deletedEntries = $event->raffleEntries()->delete();
            $this->line("  ✓ {$deletedEntries} entradas eliminadas");

            // 3. Eliminar logs de rifa
            $this->info('Eliminando logs de rifa...');
            $deletedLogs = RaffleLog::where('event_id', $event->id)->delete();
            $this->line("  ✓ {$deletedLogs} logs eliminados");

            // 4. Resetear el imex_prize_id del evento
            if ($event->imex_prize_id) {
                $this->info('Reseteando premio IMEX asignado...');
                $event->update(['imex_prize_id' => null]);
                $this->line("  ✓ Premio IMEX reseteado");
            }

            DB::commit();

            $this->line('');
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('✓ RESET COMPLETADO EXITOSAMENTE');
            $this->info('═══════════════════════════════════════════════════════');
            $this->line('');
            $this->line("Resumen:");
            $this->line("  • {$deletedEntries} entradas eliminadas");
            $this->line("  • {$deletedLogs} logs eliminados");
            $this->line("  • Stock de premios restaurado");
            $this->line("  • Premio IMEX reseteado");
            $this->line('');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('');
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('✗ ERROR AL RESETEAR LA RIFA');
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('');
            $this->error('Mensaje de error:');
            $this->error($e->getMessage());
            $this->error('');
            
            return 1;
        }
    }

    /**
     * Resetear todos los eventos
     */
    protected function resetAllEvents(bool $force = false): int
    {
        $events = Event::has('raffleEntries')->get();

        if ($events->isEmpty()) {
            $this->info('No hay eventos con entradas de rifa para resetear.');
            return 0;
        }

        $totalEntries = RaffleEntry::count();
        $totalWinners = RaffleEntry::where('status', 'won')->count();
        $totalLogs = RaffleLog::count();

        $this->warn("═══════════════════════════════════════════════════════");
        $this->warn("  RESET MASIVO DE RIFAS - TODOS LOS EVENTOS");
        $this->warn("═══════════════════════════════════════════════════════");
        $this->line('');
        $this->info("Se resetearán {$events->count()} eventos:");
        foreach ($events as $event) {
            $entries = $event->raffleEntries()->count();
            $this->line("  • {$event->name} ({$entries} entradas)");
        }
        $this->line('');
        $this->line("Total global:");
        $this->line("  • Entradas: {$totalEntries}");
        $this->line("  • Ganadores: {$totalWinners}");
        $this->line("  • Logs: {$totalLogs}");
        $this->line('');

        if (!$force) {
            $this->warn('⚠️  ADVERTENCIA: Esta acción eliminará TODAS las rifas de TODOS los eventos.');
            if (!$this->confirm('¿Estás ABSOLUTAMENTE seguro de continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($events as $event) {
            $this->line('');
            $this->info("Procesando evento: {$event->name}...");
            
            $result = $this->resetEvent($event, true);
            
            if ($result === 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $this->line('');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('✓ RESET MASIVO COMPLETADO');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line('');
        $this->line("Resultados:");
        $this->line("  • Eventos reseteados exitosamente: {$successCount}");
        if ($errorCount > 0) {
            $this->line("  • Eventos con errores: {$errorCount}");
        }
        $this->line('');

        return $errorCount > 0 ? 1 : 0;
    }
}
