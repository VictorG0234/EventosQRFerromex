<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAttendances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:reset 
                            {event? : ID del evento a resetear}
                            {--all : Resetear asistencias de todos los eventos}
                            {--force : No pedir confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetea solo las asistencias de un evento (elimina registros de attendance)';

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
                ['ID', 'Nombre', 'Fecha', 'Invitados', 'Asistencias', '% Asistencia'],
                $events->map(function ($event) {
                    $totalGuests = $event->guests()->count();
                    $totalAttendances = $event->attendances()->count();
                    $percentage = $totalGuests > 0 ? round(($totalAttendances / $totalGuests) * 100, 1) : 0;
                    return [
                        $event->id,
                        $event->name,
                        $event->event_date?->format('d/m/Y'),
                        $totalGuests,
                        $totalAttendances,
                        $percentage . '%'
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
     * Resetear asistencias de un evento específico
     */
    protected function resetEvent(Event $event, bool $force = false): int
    {
        // Obtener estadísticas antes del reset
        $totalGuests = $event->guests()->count();
        $totalAttendances = $event->attendances()->count();
        $percentage = $totalGuests > 0 ? round(($totalAttendances / $totalGuests) * 100, 1) : 0;

        // Mostrar información
        $this->warn("═══════════════════════════════════════════════════════");
        $this->warn("  RESET DE ASISTENCIAS - EVENTO: {$event->name}");
        $this->warn("═══════════════════════════════════════════════════════");
        $this->line('');
        $this->info("Estadísticas actuales:");
        $this->line("  • Total de invitados: {$totalGuests}");
        $this->line("  • Total de asistencias: {$totalAttendances}");
        $this->line("  • Porcentaje de asistencia: {$percentage}%");
        $this->line('');
        $this->info("Lo que SE ELIMINARÁ:");
        $this->line("  • {$totalAttendances} registros de asistencia");
        $this->line('');
        $this->info("Lo que SE MANTENDRÁ:");
        $this->line("  ✓ Invitados ({$totalGuests})");
        $this->line("  ✓ Códigos QR");
        $this->line("  ✓ Rifas y participaciones");
        $this->line("  ✓ Premios");
        $this->line('');

        if (!$force) {
            if (!$this->confirm('¿Estás seguro de que deseas eliminar todas las asistencias de este evento?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Eliminar todas las asistencias del evento
            $this->info('Eliminando registros de asistencia...');
            $deletedAttendances = Attendance::where('event_id', $event->id)->delete();
            $this->line("  ✓ {$deletedAttendances} asistencias eliminadas");

            DB::commit();

            $this->line('');
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('✓ RESET DE ASISTENCIAS COMPLETADO EXITOSAMENTE');
            $this->info('═══════════════════════════════════════════════════════');
            $this->line('');
            $this->line("Resumen:");
            $this->line("  • {$deletedAttendances} asistencias eliminadas");
            $this->line("  • {$totalGuests} invitados conservados");
            $this->line("  • Ahora puedes registrar nuevas asistencias");
            $this->line('');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('');
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('✗ ERROR AL RESETEAR LAS ASISTENCIAS');
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('');
            $this->error('Mensaje de error:');
            $this->error($e->getMessage());
            $this->error('');
            
            return 1;
        }
    }

    /**
     * Resetear asistencias de todos los eventos
     */
    protected function resetAllEvents(bool $force = false): int
    {
        $events = Event::has('attendances')->get();

        if ($events->isEmpty()) {
            $this->info('No hay eventos con asistencias registradas para resetear.');
            return 0;
        }

        $totalAttendances = Attendance::count();
        $totalGuests = 0;

        $this->warn("═══════════════════════════════════════════════════════");
        $this->warn("  RESET MASIVO DE ASISTENCIAS - TODOS LOS EVENTOS");
        $this->warn("═══════════════════════════════════════════════════════");
        $this->line('');
        $this->info("Se resetearán {$events->count()} eventos:");
        foreach ($events as $event) {
            $attendances = $event->attendances()->count();
            $guests = $event->guests()->count();
            $totalGuests += $guests;
            $percentage = $guests > 0 ? round(($attendances / $guests) * 100, 1) : 0;
            $this->line("  • {$event->name} ({$attendances} asistencias, {$percentage}%)");
        }
        $this->line('');
        $this->line("Total global:");
        $this->line("  • Asistencias a eliminar: {$totalAttendances}");
        $this->line("  • Invitados que se conservarán: {$totalGuests}");
        $this->line('');

        if (!$force) {
            $this->warn('⚠️  ADVERTENCIA: Esta acción eliminará TODAS las asistencias de TODOS los eventos.');
            $this->warn('⚠️  Los invitados, QRs y rifas se mantendrán intactos.');
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
        $this->info('✓ RESET MASIVO DE ASISTENCIAS COMPLETADO');
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
