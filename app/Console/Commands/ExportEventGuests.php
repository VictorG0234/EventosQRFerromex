<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportEventGuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:export {event_id : ID del evento}
                            {--output= : Ruta del archivo de salida (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar todos los invitados de un evento a un archivo CSV en formato de importación';

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

        $this->info("📋 Exportando invitados del evento: {$event->name} (ID: {$eventId})");
        $this->newLine();

        // Obtener todos los invitados del evento
        $guests = Guest::where('event_id', $eventId)
            ->orderBy('id')
            ->get();

        if ($guests->isEmpty()) {
            $this->warn("⚠️  No hay invitados en este evento.");
            return 0;
        }

        $this->info("Total de invitados encontrados: " . $guests->count());
        $this->newLine();

        // Generar el contenido del CSV
        $csvContent = $this->generateCsvContent($guests);

        // Determinar la ruta de salida
        if (!$outputPath) {
            $filename = "evento_{$eventId}_guests_" . date('Y-m-d_His') . ".csv";
            $outputPath = storage_path('app/exports/' . $filename);
            
            // Crear directorio si no existe
            $exportDir = storage_path('app/exports');
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
        }

        // Guardar el archivo
        file_put_contents($outputPath, $csvContent);

        $this->info("✅ Archivo CSV generado exitosamente:");
        $this->line("   📁 {$outputPath}");
        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   • Total de invitados exportados: " . $guests->count());
        $this->line("   • Tamaño del archivo: " . $this->formatBytes(filesize($outputPath)));

        return 0;
    }

    /**
     * Generar el contenido del CSV en formato de importación
     */
    protected function generateCsvContent($guests): string
    {
        // Headers del CSV (formato de importación)
        $headers = [
            'Compañia',
            'NumEmpleado',
            'NombreCompleto',
            'Correo',
            'Puesto',
            'NivelDePuesto',
            'Localidad',
            'FechaAlta',
            'Descripcion',
            'CategoriaRifa'
        ];

        $lines = [];
        
        // Agregar headers
        $lines[] = $this->csvEscape($headers);

        // Agregar datos de cada invitado
        foreach ($guests as $guest) {
            $row = [
                $guest->compania ?? '',
                $guest->numero_empleado ?? '',
                $guest->nombre_completo ?? '',
                $guest->correo ?? '',
                $guest->puesto ?? '',
                $guest->nivel_de_puesto ?? '',
                $guest->localidad ?? '',
                $guest->fecha_alta ? $guest->fecha_alta->format('Y-m-d') : '',
                $guest->descripcion ?? '',
                $guest->categoria_rifa ?? ''
            ];

            $lines[] = $this->csvEscape($row);
        }

        return implode("\n", $lines);
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

