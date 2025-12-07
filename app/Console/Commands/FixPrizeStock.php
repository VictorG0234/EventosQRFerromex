<?php

namespace App\Console\Commands;

use App\Models\Prize;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPrizeStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prizes:fix-stock 
                            {--event-id= : ID del evento específico a corregir}
                            {--prize-id= : ID del premio específico a corregir}
                            {--dry-run : Solo mostrar qué se corregiría sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corregir el stock de premios que tienen stock=0 pero no tienen ganadores registrados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Iniciando corrección de stock de premios...');
        
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios reales');
        }

        $eventId = $this->option('event-id');
        $prizeId = $this->option('prize-id');

        // Construir query
        $query = Prize::query();
        
        if ($prizeId) {
            $query->where('id', $prizeId);
        } elseif ($eventId) {
            $query->where('event_id', $eventId);
        }

        // Excluir el premio especial "Rifa General"
        $query->where('name', '!=', 'Rifa General');

        $prizes = $query->get();
        
        if ($prizes->isEmpty()) {
            $this->warn('No se encontraron premios para corregir.');
            return Command::SUCCESS;
        }

        $this->info("📋 Analizando {$prizes->count()} premio(s)...");
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;
        $issues = [];

        foreach ($prizes as $prize) {
            $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
            
            // Verificar si hay problema: stock = 0 pero no hay ganadores
            if ($prize->stock === 0 && $winnersCount === 0) {
                $issues[] = [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'event_id' => $prize->event_id,
                    'current_stock' => $prize->stock,
                    'winners_count' => $winnersCount,
                    'action' => 'restore'
                ];
            } elseif ($prize->stock > 0 && $winnersCount > 0) {
                // Inconsistencia: hay ganadores pero stock > 0
                $issues[] = [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'event_id' => $prize->event_id,
                    'current_stock' => $prize->stock,
                    'winners_count' => $winnersCount,
                    'action' => 'warning'
                ];
            } else {
                $skippedCount++;
            }
        }

        if (empty($issues)) {
            $this->info('✅ No se encontraron problemas de stock. Todos los premios están correctos.');
            return Command::SUCCESS;
        }

        // Mostrar tabla de problemas encontrados
        $this->table(
            ['ID', 'Premio', 'Evento ID', 'Stock Actual', 'Ganadores', 'Acción'],
            array_map(function ($issue) {
                return [
                    $issue['id'],
                    $issue['name'],
                    $issue['event_id'],
                    $issue['current_stock'],
                    $issue['winners_count'],
                    $issue['action'] === 'restore' ? '🔄 Restaurar' : '⚠️  Advertencia'
                ];
            }, $issues)
        );

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   - Premios con problemas: " . count($issues));
        $this->line("   - Premios a restaurar: " . count(array_filter($issues, fn($i) => $i['action'] === 'restore')));
        $this->line("   - Premios con advertencias: " . count(array_filter($issues, fn($i) => $i['action'] === 'warning')));
        $this->line("   - Premios sin problemas: {$skippedCount}");

        if ($isDryRun) {
            $this->newLine();
            $this->warn('⚠️  DRY-RUN completado. Ejecuta sin --dry-run para aplicar los cambios.');
            return Command::SUCCESS;
        }

        // Confirmar antes de aplicar cambios
        if (!$this->confirm('¿Deseas aplicar las correcciones?', true)) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('🔧 Aplicando correcciones...');

        DB::beginTransaction();
        try {
            foreach ($issues as $issue) {
                if ($issue['action'] === 'restore') {
                    $prize = Prize::find($issue['id']);
                    if ($prize) {
                        $prize->restoreStockFromWinners();
                        $fixedCount++;
                        $this->line("   ✅ Restaurado stock para: {$prize->name} (ID: {$prize->id})");
                    }
                } elseif ($issue['action'] === 'warning') {
                    // Solo registrar en log, no cambiar automáticamente
                    \Log::warning('Inconsistencia detectada en premio', [
                        'prize_id' => $issue['id'],
                        'prize_name' => $issue['name'],
                        'winners_count' => $issue['winners_count'],
                        'current_stock' => $issue['current_stock']
                    ]);
                    $this->line("   ⚠️  Advertencia registrada para: {$issue['name']} (ID: {$issue['id']})");
                }
            }

            DB::commit();
            
            $this->newLine();
            $this->info("✅ Corrección completada exitosamente!");
            $this->info("   - Premios corregidos: {$fixedCount}");
            $this->info("   - Advertencias registradas: " . count(array_filter($issues, fn($i) => $i['action'] === 'warning')));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error al aplicar correcciones: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

