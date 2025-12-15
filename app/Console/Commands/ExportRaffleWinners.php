<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\RaffleEntry;
use App\Models\RaffleLog;
use Illuminate\Console\Command;

class ExportRaffleWinners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'winners:export {event_id : ID del evento}
                            {--output= : Ruta del archivo de salida (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar todos los ganadores de rifas de un evento a un archivo CSV';

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

        $this->info("🏆 Exportando ganadores del evento: {$event->name} (ID: {$eventId})");
        $this->newLine();

        // Obtener todos los ganadores del evento
        $winners = RaffleEntry::where('event_id', $eventId)
            ->where('status', 'won')
            ->with(['guest', 'prize'])
            ->orderBy('drawn_at', 'desc')
            ->get();

        if ($winners->isEmpty()) {
            $this->warn("⚠️  No hay ganadores en este evento.");
            return 0;
        }

        $this->info("Total de ganadores encontrados: " . $winners->count());
        $this->newLine();

        // Obtener los tipos de rifa desde RaffleLog
        $raffleLogs = RaffleLog::where('event_id', $eventId)
            ->whereIn('guest_id', $winners->pluck('guest_id'))
            ->whereIn('prize_id', $winners->pluck('prize_id'))
            ->get()
            ->keyBy(function ($log) {
                return $log->guest_id . '_' . $log->prize_id;
            });

        // Generar el contenido del CSV
        $csvContent = $this->generateCsvContent($winners, $raffleLogs);

        // Determinar la ruta de salida
        if (!$outputPath) {
            $filename = "evento_{$eventId}_ganadores_" . date('Y-m-d_His') . ".csv";
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
        $this->line("   • Total de ganadores exportados: " . $winners->count());
        $this->line("   • Tamaño del archivo: " . $this->formatBytes(filesize($outputPath)));

        return 0;
    }

    /**
     * Generar el contenido del CSV
     */
    protected function generateCsvContent($winners, $raffleLogs): string
    {
        // Headers del CSV
        $headers = [
            'TipoRifa',
            'Premio',
            'NumEmpleado',
            'NombreEmpleado',
            'Descripcion',
            'CategoriaRifa'
        ];

        $lines = [];
        
        // Agregar headers
        $lines[] = $this->csvEscape($headers);

        // Agregar datos de cada ganador
        foreach ($winners as $winner) {
            $guest = $winner->guest;
            $prize = $winner->prize;
            
            // Obtener tipo de rifa
            $raffleType = $this->getRaffleType($winner, $raffleLogs, $prize);
            
            $row = [
                $raffleType,
                $prize->name ?? '',
                $guest->numero_empleado ?? '',
                $guest->nombre_completo ?? '',
                $guest->descripcion ?? '',
                $guest->categoria_rifa ?? ''
            ];

            $lines[] = $this->csvEscape($row);
        }

        return implode("\n", $lines);
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

