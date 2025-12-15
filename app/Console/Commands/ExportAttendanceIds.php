<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Attendance;
use Illuminate\Console\Command;

class ExportAttendanceIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendances:export-ids {event_id : ID del evento}
                            {--output= : Ruta del archivo de salida (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar los números de empleado de todas las asistencias de un evento a un archivo de texto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');
        $outputPath = $this->option('output');

        // Buscar el evento
        $event = Event::find($eventId);
        
        if (!$event) {
            $this->error("❌ No se encontró el evento con ID: {$eventId}");
            return 1;
        }

        $this->info("📋 Exportando números de empleado de asistencias del evento: {$event->name} (ID: {$eventId})");
        $this->newLine();

        // Obtener todos los números de empleado de las asistencias
        $employeeNumbers = Attendance::where('event_id', $eventId)
            ->with('guest')
            ->get()
            ->pluck('guest.numero_empleado')
            ->filter()
            ->unique()
            ->values()
            ->sort()
            ->values();

        if ($employeeNumbers->isEmpty()) {
            $this->warn("⚠️  No hay asistencias registradas en este evento.");
            return 0;
        }

        $this->info("Total de números de empleado únicos encontrados: " . $employeeNumbers->count());
        $this->newLine();

        // Determinar la ruta de salida
        if (!$outputPath) {
            $filename = "evento_{$eventId}_attendance_empleados_" . date('Y-m-d_His') . ".txt";
            $outputPath = storage_path('app/exports/' . $filename);
            
            // Crear directorio si no existe
            $exportDir = storage_path('app/exports');
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
        }

        // Generar el contenido del archivo (un número de empleado por línea)
        $content = $employeeNumbers->implode("\n");

        // Guardar el archivo
        file_put_contents($outputPath, $content);

        $this->info("✅ Archivo de IDs generado exitosamente:");
        $this->line("   📁 {$outputPath}");
        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   • Total de números de empleado exportados: " . $employeeNumbers->count());
        $this->line("   • Tamaño del archivo: " . $this->formatBytes(filesize($outputPath)));
        $this->newLine();
        $this->comment("💡 Puedes usar este archivo con el comando: attendance:import-ids");

        return 0;
    }

    /**
     * Formatear bytes a formato legible
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

