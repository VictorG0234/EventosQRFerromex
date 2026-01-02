<?php

namespace App\Console\Commands;

use App\Models\Guest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:clean-orphans 
                            {--dry-run : Solo mostrar archivos que se eliminarían sin eliminarlos}
                            {--force : No pedir confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina archivos QR huérfanos (de invitados/eventos que ya no existen)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  LIMPIEZA DE CÓDIGOS QR HUÉRFANOS');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line('');

        // Obtener todos los archivos QR del storage
        $qrPath = 'qr_codes';
        if (!Storage::disk('public')->exists($qrPath)) {
            $this->info('No existe el directorio de códigos QR.');
            return 0;
        }

        $allQrFiles = Storage::disk('public')->files($qrPath);
        $totalFiles = count($allQrFiles);

        if ($totalFiles === 0) {
            $this->info('No hay archivos QR en el sistema.');
            return 0;
        }

        $this->info("Total de archivos QR encontrados: {$totalFiles}");
        $this->line('');

        // Obtener todos los QR paths de la base de datos
        $validQrPaths = Guest::whereNotNull('qr_code_path')
            ->pluck('qr_code_path')
            ->toArray();

        $this->info("Archivos QR referenciados en base de datos: " . count($validQrPaths));
        $this->line('');

        // Encontrar archivos huérfanos
        $orphanFiles = [];
        foreach ($allQrFiles as $file) {
            if (!in_array($file, $validQrPaths)) {
                $orphanFiles[] = $file;
            }
        }

        $orphanCount = count($orphanFiles);

        if ($orphanCount === 0) {
            $this->info('✓ No se encontraron archivos QR huérfanos.');
            $this->info('Todos los archivos están correctamente referenciados.');
            return 0;
        }

        $this->warn("Se encontraron {$orphanCount} archivos QR huérfanos.");
        $this->line('');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: Archivos que se eliminarían:');
            $this->line('');
            
            foreach (array_slice($orphanFiles, 0, 20) as $file) {
                $size = Storage::disk('public')->size($file);
                $sizeKB = round($size / 1024, 2);
                $this->line("  • {$file} ({$sizeKB} KB)");
            }
            
            if ($orphanCount > 20) {
                $remaining = $orphanCount - 20;
                $this->line("  ... y {$remaining} archivo(s) más");
            }
            
            $this->line('');
            $this->info('Ejecuta el comando sin --dry-run para eliminar estos archivos.');
            return 0;
        }

        if (!$force) {
            $this->line('Archivos a eliminar (primeros 20):');
            foreach (array_slice($orphanFiles, 0, 20) as $file) {
                $size = Storage::disk('public')->size($file);
                $sizeKB = round($size / 1024, 2);
                $this->line("  • {$file} ({$sizeKB} KB)");
            }
            
            if ($orphanCount > 20) {
                $remaining = $orphanCount - 20;
                $this->line("  ... y {$remaining} archivo(s) más");
            }
            
            $this->line('');
            $this->warn("⚠️  Se eliminarán {$orphanCount} archivos QR huérfanos.");
            
            if (!$this->confirm('¿Estás seguro de continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Eliminar archivos huérfanos
        $this->info('Eliminando archivos QR huérfanos...');
        $deletedCount = 0;
        $errorCount = 0;
        $totalSize = 0;

        $progressBar = $this->output->createProgressBar($orphanCount);
        $progressBar->start();

        foreach ($orphanFiles as $file) {
            try {
                $size = Storage::disk('public')->size($file);
                $totalSize += $size;
                
                Storage::disk('public')->delete($file);
                $deletedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        $totalSizeMB = round($totalSize / 1024 / 1024, 2);

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('✓ LIMPIEZA COMPLETADA');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line('');
        $this->line("Resumen:");
        $this->line("  • Archivos eliminados: {$deletedCount}");
        if ($errorCount > 0) {
            $this->line("  • Errores: {$errorCount}");
        }
        $this->line("  • Espacio liberado: {$totalSizeMB} MB");
        $this->line('');

        return $errorCount > 0 ? 1 : 0;
    }
}
