<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Attendance;
use App\Models\RaffleEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DeleteEventGuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:delete {event_id : ID del evento}
                            {--force : No solicitar confirmación}
                            {--keep-qr : Mantener los archivos QR en storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar todos los invitados de un evento junto con sus registros relacionados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');
        $force = $this->option('force');
        $keepQr = $this->option('keep-qr');

        // Buscar el evento
        $event = Event::find($eventId);
        
        if (!$event) {
            $this->error("❌ No se encontró el evento con ID: {$eventId}");
            return 1;
        }

        $this->warn("⚠️  ATENCIÓN: Estás a punto de eliminar todos los invitados del evento:");
        $this->info("   Evento: {$event->name}");
        $this->info("   ID: {$event->id}");
        $this->newLine();

        // Contar registros a eliminar
        $guestsCount = Guest::where('event_id', $eventId)->count();
        $attendancesCount = Attendance::whereHas('guest', function($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })->count();
        $raffleEntriesCount = RaffleEntry::where('event_id', $eventId)->count();

        if ($guestsCount === 0) {
            $this->info("✅ No hay invitados para eliminar en este evento.");
            return 0;
        }

        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Invitados', $guestsCount],
                ['Asistencias', $attendancesCount],
                ['Participaciones en rifas', $raffleEntriesCount],
            ]
        );

        $this->newLine();
        $this->error("⚠️  Esta acción NO se puede deshacer.");
        
        if (!$keepQr) {
            $this->warn("   Los códigos QR también serán eliminados del storage.");
        } else {
            $this->info("   Los códigos QR se mantendrán en storage (--keep-qr).");
        }

        $this->newLine();

        // Confirmación
        if (!$force) {
            $confirmation = $this->ask("Para continuar, escribe el nombre del evento: {$event->name}");
            
            if ($confirmation !== $event->name) {
                $this->error("❌ El nombre no coincide. Operación cancelada.");
                return 1;
            }

            if (!$this->confirm('¿Estás completamente seguro de eliminar estos registros?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->newLine();
        $this->info("🗑️  Eliminando registros...");
        
        try {
            DB::beginTransaction();

            // Obtener invitados antes de eliminar (para borrar QR)
            $guests = Guest::where('event_id', $eventId)->get();
            $qrPaths = [];
            
            if (!$keepQr) {
                foreach ($guests as $guest) {
                    if (!empty($guest->qr_code_path)) {
                        $qrPaths[] = $guest->qr_code_path;
                    }
                }
            }

            // Eliminar en orden: primero las dependencias, luego los invitados
            
            // 1. Eliminar asistencias
            $deletedAttendances = Attendance::whereHas('guest', function($query) use ($eventId) {
                $query->where('event_id', $eventId);
            })->delete();

            // 2. Eliminar participaciones en rifas
            $deletedRaffleEntries = RaffleEntry::where('event_id', $eventId)->delete();

            // 3. Eliminar invitados (también eliminará invitaciones si existen)
            $deletedGuests = Guest::where('event_id', $eventId)->delete();

            DB::commit();

            // Eliminar archivos QR del storage
            $deletedQrFiles = 0;
            if (!$keepQr && !empty($qrPaths)) {
                $this->info("🗑️  Eliminando archivos QR del storage...");
                foreach ($qrPaths as $qrPath) {
                    if (Storage::disk('public')->exists($qrPath)) {
                        Storage::disk('public')->delete($qrPath);
                        $deletedQrFiles++;
                    }
                }
            }

            // Mostrar resultados
            $this->newLine();
            $this->info("✅ Eliminación completada exitosamente:");
            $this->table(
                ['Tipo', 'Eliminados'],
                [
                    ['Invitados', $deletedGuests],
                    ['Asistencias', $deletedAttendances],
                    ['Participaciones en rifas', $deletedRaffleEntries],
                    ['Archivos QR', $deletedQrFiles],
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error al eliminar registros: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
