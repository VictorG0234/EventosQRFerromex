<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestAttendances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:generate-attendances {event_id? : ID del evento (opcional, si no se proporciona se usará el último evento)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera registros de asistencia para todos los invitados de un evento (solo para pruebas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  Este comando es SOLO para pruebas y desarrollo');
        $this->newLine();

        // Obtener evento
        $eventId = $this->argument('event_id');
        
        if ($eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                $this->error("❌ No se encontró el evento con ID: {$eventId}");
                return 1;
            }
        } else {
            $event = Event::latest()->first();
            if (!$event) {
                $this->error('❌ No hay eventos en la base de datos');
                return 1;
            }
        }

        $this->info("📅 Evento seleccionado: {$event->name} (ID: {$event->id})");
        $this->newLine();

        // Obtener todos los invitados del evento
        $guests = Guest::where('event_id', $event->id)->get();
        
        if ($guests->isEmpty()) {
            $this->error('❌ Este evento no tiene invitados registrados');
            return 1;
        }

        $this->info("👥 Total de invitados: {$guests->count()}");
        
        // Verificar cuántos ya tienen asistencia
        $existingAttendances = Attendance::where('event_id', $event->id)->count();
        $this->info("✅ Asistencias ya registradas: {$existingAttendances}");
        $this->newLine();

        if (!$this->confirm('¿Deseas generar asistencias de prueba para TODOS los invitados que aún no tienen registro?', true)) {
            $this->warn('⚠️  Operación cancelada');
            return 0;
        }

        $this->newLine();
        $this->info('🚀 Generando asistencias de prueba...');
        $this->newLine();

        $bar = $this->output->createProgressBar($guests->count());
        $bar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($guests as $guest) {
            try {
                // Verificar si ya tiene asistencia
                $existingAttendance = Attendance::where('event_id', $event->id)
                    ->where('guest_id', $guest->id)
                    ->first();

                if ($existingAttendance) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Crear asistencia con timestamp aleatorio durante el día del evento
                $eventDate = $event->event_date;
                $randomTime = now()->copy()
                    ->setDate($eventDate->year, $eventDate->month, $eventDate->day)
                    ->setTime(rand(8, 20), rand(0, 59), rand(0, 59));

                Attendance::create([
                    'event_id' => $event->id,
                    'guest_id' => $guest->id,
                    'scanned_at' => $randomTime,
                    'scanned_by' => 'Sistema de Pruebas',
                    'scan_metadata' => [
                        'method' => 'test_generation',
                        'ip' => '127.0.0.1',
                        'user_agent' => 'Test Command',
                        'note' => 'Asistencia generada automáticamente para pruebas'
                    ]
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error con invitado ID {$guest->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('✨ Proceso completado');
        $this->newLine();
        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['✅ Asistencias creadas', $created],
                ['⏭️  Ya existían (omitidas)', $skipped],
                ['❌ Errores', $errors],
                ['📊 Total procesados', $guests->count()],
            ]
        );

        // Estadísticas finales del evento
        $totalAttendances = Attendance::where('event_id', $event->id)->count();
        $totalGuests = Guest::where('event_id', $event->id)->count();
        $percentage = $totalGuests > 0 ? round(($totalAttendances / $totalGuests) * 100, 2) : 0;

        $this->newLine();
        $this->info("📈 Estadísticas finales del evento:");
        $this->line("   • Total invitados: {$totalGuests}");
        $this->line("   • Total asistencias: {$totalAttendances}");
        $this->line("   • Porcentaje de asistencia: {$percentage}%");
        $this->newLine();

        return 0;
    }
}
