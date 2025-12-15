<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Prize;
use Illuminate\Console\Command;

class ExportPrizes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prizes:export {event_id : ID del evento}
                            {--output= : Ruta del archivo de salida (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar todos los premios de un evento a un archivo CSV';

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

        $this->info("🎁 Exportando premios del evento: {$event->name} (ID: {$eventId})");
        $this->newLine();

        // Obtener todos los premios del evento (excluyendo Rifa General)
        $prizes = Prize::where('event_id', $eventId)
            ->where('name', '!=', 'Rifa General')
            ->orderBy('id')
            ->get();

        if ($prizes->isEmpty()) {
            $this->warn("⚠️  No hay premios registrados en este evento (excluyendo Rifa General).");
            return 0;
        }

        $this->info("Total de premios encontrados: " . $prizes->count() . " (Rifa General omitida)");
        $this->newLine();

        // Generar el contenido del CSV
        $csvContent = $this->generateCsvContent($prizes);

        // Determinar la ruta de salida
        if (!$outputPath) {
            $filename = "evento_{$eventId}_premios_" . date('Y-m-d_His') . ".csv";
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
        $this->line("   • Total de premios exportados: " . $prizes->count());
        $this->line("   • Tamaño del archivo: " . $this->formatBytes(filesize($outputPath)));

        return 0;
    }

    /**
     * Generar el contenido del CSV
     */
    protected function generateCsvContent($prizes): string
    {
        // Headers del CSV (mismo formato que la plantilla)
        $headers = [
            'Titulo',
            'Descripcion',
            'Categoria',
            'Cantidad',
            'Valor',
            'Activo'
        ];

        $lines = [];
        
        // Agregar headers
        $lines[] = $this->csvEscape($headers);

        // Agregar datos de cada premio
        foreach ($prizes as $prize) {
            $row = [
                $prize->name ?? '',
                $prize->description ?? '',
                $prize->category ?? '',
                '1', // Cantidad siempre es 1
                $prize->value ? number_format($prize->value, 2, '.', '') : '',
                $prize->active ? 'Sí' : 'No'
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

