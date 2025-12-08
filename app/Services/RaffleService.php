<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\RaffleLog;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RaffleService
{
    /**
     * Create raffle entries for all eligible guests for a specific prize
     * @param Prize $prize
     * @param string $raffleType Tipo de rifa: 'public' o 'general' (default: 'general')
     */
    public function createRaffleEntries(Prize $prize, string $raffleType = 'general'): array
    {
        try {
            DB::beginTransaction();

            $eligibleGuests = $this->getEligibleGuests($prize, $raffleType);
            
            if ($eligibleGuests->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay invitados elegibles para este premio según las reglas de la rifa ' . $raffleType . '. Verifica que haya invitados que cumplan todas las condiciones requeridas.',
                    'entries_created' => 0,
                    'total_eligible' => 0
                ];
            }
            
            $entriesCreated = 0;
            $excludedByRule4 = 0;
            $alreadyHasEntry = 0;

            foreach ($eligibleGuests as $guest) {
                // Check if guest already has an entry for this prize
                $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                    ->where('prize_id', $prize->id)
                    ->first();

                if ($existingEntry) {
                    $alreadyHasEntry++;
                    continue; // Ya tiene entrada, no crear otra
                }

                // REGLA 4: Un Guest no puede GANAR más de un Prize (solo para RIFA PÚBLICA)
                // Esto significa que puede participar en múltiples premios, pero solo puede GANAR uno
                $canEnter = true;
                if ($raffleType === 'public') {
                    // Verificar si el guest ya GANÓ un prize en otro premio (no pendiente, solo ganado)
                    $hasWonOtherPrize = RaffleEntry::where('guest_id', $guest->id)
                        ->where('prize_id', '!=', $prize->id)
                        ->where('status', 'won')
                        ->exists();
                    $canEnter = !$hasWonOtherPrize;
                    if ($hasWonOtherPrize) {
                        $excludedByRule4++;
                    }
                }

                if ($canEnter) {
                    RaffleEntry::enterRaffle($guest, $prize, [
                        'auto_entered' => true,
                        'entered_by' => 'system'
                    ]);
                    $entriesCreated++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'entries_created' => $entriesCreated,
                'total_eligible' => $eligibleGuests->count(),
                'already_entered' => $eligibleGuests->count() - $entriesCreated,
                'excluded_by_rule4' => $excludedByRule4 ?? 0
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create raffle entries', [
                'prize_id' => $prize->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform raffle draw for a specific prize
     * @param Prize $prize
     * @param int $winnersCount
     * @param bool $sendNotification
     * @param string $raffleType Tipo de rifa: 'public' o 'general' (default: 'general')
     */
    public function drawRaffle(Prize $prize, int $winnersCount = 1, bool $sendNotification = true, string $raffleType = 'general'): array
    {
        try {
            DB::beginTransaction();

            // Get all pending entries for this prize
            $pendingEntries = RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->with('guest')
                ->get();

            if ($pendingEntries->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay participaciones pendientes para este premio'
                ];
            }

            // Refrescar el premio para obtener el stock actualizado
            $prize->refresh();
            
            // CORRECCIÓN AUTOMÁTICA: Si el stock es 0 pero no hay ganadores registrados,
            // restaurar el stock automáticamente (corrige datos corrompidos)
            if ($prize->stock <= 0) {
                $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
                if ($winnersCount === 0) {
                    // No hay ganadores registrados pero el stock es 0 - restaurar stock
                    $prize->restoreStockFromWinners();
                    $prize->refresh();
                    Log::info('Stock restaurado automáticamente para premio sin ganadores', [
                        'prize_id' => $prize->id,
                        'prize_name' => $prize->name,
                        'restored_stock' => $prize->stock
                    ]);
                }
            }
            
            // Check if we have enough stock
            // Si el stock siempre es 1, verificar que haya al menos 1 disponible
            $availableStock = max($prize->stock, 0);
            $winnersCount = min($winnersCount, max($availableStock, 1));
            
            if ($winnersCount <= 0 || $prize->stock <= 0) {
                return [
                    'success' => false,
                    'error' => 'No hay stock disponible para este premio. Stock actual: ' . $prize->stock
                ];
            }

            // Aplicar reglas según el tipo de rifa
            $eligibleEntries = $pendingEntries;
            $existingIMEXWinner = false; // Inicializar variable
            
            // REGLAS DE RIFA PÚBLICA (solo para rifa pública)
            if ($raffleType === 'public') {
                // Obtener el evento y verificar/establecer el premio IMEX aleatorio
                $event = $prize->event;
                
                // Si no hay premio IMEX seleccionado, seleccionarlo aleatoriamente
                if (!$event->imex_prize_id) {
                    // Obtener todos los premios activos del evento donde IMEX puede participar
                    // (excluyendo "Automovil" y "Rifa General")
                    $eligiblePrizesForIMEX = Prize::where('event_id', $event->id)
                        ->where('active', true)
                        ->where('name', '!=', 'Rifa General')
                        ->whereRaw('LOWER(name) != ?', ['automovil'])
                        ->get();
                    
                    if ($eligiblePrizesForIMEX->isNotEmpty()) {
                        // Seleccionar aleatoriamente un premio
                        $selectedIMEXPrize = $eligiblePrizesForIMEX->random();
                        $event->imex_prize_id = $selectedIMEXPrize->id;
                        $event->save();
                    }
                }
                
                // REGLA 1: Debe haber si o si 1 ganador con la Compañía IMEX (obligatorio)
                // Verificar si este premio es el seleccionado para tener ganador IMEX
                $isIMEXPrize = $event->imex_prize_id === $prize->id;
                
                // Verificar si ya existe un ganador IMEX en este premio específico
                $existingIMEXWinnerInThisPrize = RaffleEntry::where('prize_id', $prize->id)
                    ->whereHas('guest', function ($q) {
                        $q->where('compania', 'IMEX');
                    })
                    ->where('status', 'won')
                    ->exists();
                
                // Verificar si ya existe un ganador con compañía IMEX en otros premios (excluyendo este premio y la rifa general)
                $existingIMEXWinnerInOtherPrizes = RaffleEntry::whereHas('prize', function ($q) use ($prize) {
                    $q->where('event_id', $prize->event_id)
                      ->where('id', '!=', $prize->id) // Excluir este premio
                      ->where('name', '!=', 'Rifa General'); // Excluir premio de rifa general
                })
                ->whereHas('guest', function ($q) {
                    $q->where('compania', 'IMEX');
                })
                ->where('status', 'won')
                ->exists();

                // Filtrar candidatos según reglas adicionales de rifa pública
                // NOTA: Las entradas (pendingEntries) incluyen a todos para la animación
                // Aquí filtramos quién puede GANAR (no quién aparece en animación)
                $eligibleEntries = $pendingEntries->filter(function ($entry) use ($prize, $isIMEXPrize, $existingIMEXWinnerInThisPrize) {
                    $guest = $entry->guest;
                    
                    // REGLA 4: Un Guest no puede GANAR más de un Prize (RIFA PÚBLICA)
                    // EXCEPCIÓN: El Automóvil puede ser ganado aunque ya se haya ganado otro premio
                    if (strtolower($prize->name) !== 'automovil') {
                        $hasWonOtherPrize = RaffleEntry::where('guest_id', $guest->id)
                            ->where('prize_id', '!=', $prize->id)
                            ->where('status', 'won')
                            ->exists();
                        
                        if ($hasWonOtherPrize) {
                            return false; // Ya ganó otro premio, no puede ganar este
                        }
                    }
                    
                    // REGLA ESPECIAL: Si este premio es el imex_prize_id, SOLO IMEX pueden ganar
                    // (pero GMXT y otros aparecen en las entradas para la animación)
                    if ($isIMEXPrize) {
                        // Solo permitir IMEX para ganar este premio
                        if ($guest->compania !== 'IMEX') {
                            return false; // GMXT y otros no pueden ganar, pero aparecen en animación
                        }
                        // Si ya tiene ganador IMEX, excluir otros IMEX
                        if ($existingIMEXWinnerInThisPrize) {
                            return false;
                        }
                        return true; // IMEX puede ganar este premio
                    }
                    
                    // Para premios que NO son el imex_prize_id:
                    // IMEX puede aparecer en la animación pero NO puede ganar
                    if ($guest->compania === 'IMEX') {
                        // Excluir IMEX de poder ganar (pero mantener en entradas para animación)
                        return false;
                    }

                    // REGLA 2: Si Compañía del Guest es IMEX no puede ganar Prize con name Automovil
                    // (ya se excluyó arriba, pero por seguridad)
                    if (strtolower($prize->name) === 'automovil' && $guest->compania === 'IMEX') {
                        return false;
                    }

                    // REGLA 11: Si Descripción del Guest es Subdirectores no puede ganar prize con name Automovil
                    if (strtolower($prize->name) === 'automovil' && $guest->descripcion === 'Subdirectores') {
                        return false;
                    }

                    return true;
                });
            }
            
            // REGLAS DE RIFA GENERAL
            if ($raffleType === 'general') {
                $event = $prize->event;
                
                // REGLA CRÍTICA: Filtrar ganadores de rifa pública
                // Los ganadores de RIFA PÚBLICA no pueden participar en RIFA GENERAL
                $eligibleEntries = $pendingEntries->filter(function ($entry) use ($prize) {
                    $guest = $entry->guest;
                    
                    // Verificar si ya ganó en rifa pública (cualquier premio excepto Rifa General)
                    $hasWonPublicRaffle = RaffleEntry::where('guest_id', $guest->id)
                        ->where('event_id', $prize->event_id)
                        ->where('status', 'won')
                        ->whereHas('prize', function ($q) {
                            $q->where('name', '!=', 'Rifa General');
                        })
                        ->exists();
                    
                    // Excluir si ya ganó en rifa pública
                    if ($hasWonPublicRaffle) {
                        return false;
                    }
                    
                    return true;
                });
                
                // REGLA: En rifa general debe haber máximo 2 ganadores IMEX (por evento)
                // Verificar cuántos ganadores IMEX hay en la rifa general (excluyendo el premio especial "Rifa General")
                $generalPrize = $this->getOrCreateGeneralRafflePrize($event);
                $existingInexWinnersCount = RaffleEntry::where('event_id', $event->id)
                    ->where('prize_id', '!=', $generalPrize->id) // Excluir premio especial "Rifa General"
                    ->where('status', 'won')
                    ->whereHas('guest', function ($q) {
                        $q->where('compania', 'IMEX');
                    })
                    ->count();
                
                $maxInexWinners = 2;
                $imexSlotsAvailable = $maxInexWinners - $existingInexWinnersCount;
                
                // Si hay slots disponibles para IMEX y hay participantes IMEX, priorizarlos
                if ($imexSlotsAvailable > 0) {
                    $imexEntries = $eligibleEntries->filter(function ($entry) {
                        $compania = strtoupper(trim($entry->guest->compania ?? ''));
                        return $compania === 'IMEX';
                    });
                    
                    if ($imexEntries->isNotEmpty()) {
                        // Priorizar IMEX: seleccionar primero un IMEX si hay slots disponibles
                        $imexWinnersToSelect = min($imexSlotsAvailable, $imexEntries->count(), $winnersCount);
                        
                        if ($imexWinnersToSelect > 0) {
                            $imexWinners = $imexEntries->shuffle()->take($imexWinnersToSelect);
                            $winners = collect($imexWinners);
                            
                            // Si necesitamos más ganadores, completar con no-IMEX
                            $remainingWinnersNeeded = $winnersCount - $winners->count();
                            if ($remainingWinnersNeeded > 0) {
                                $nonInexEntries = $eligibleEntries->reject(function ($entry) use ($imexWinners) {
                                    return $imexWinners->contains('id', $entry->id);
                                });
                                
                                if ($nonInexEntries->isNotEmpty()) {
                                    $additionalWinners = $nonInexEntries->shuffle()->take($remainingWinnersNeeded);
                                    $winners = $winners->merge($additionalWinners)->shuffle();
                                }
                            }
                            
                            // shuffle the winners
                            $winnerIds = $winners->pluck('id')->toArray();
                        }
                    }
                }
            }

            if ($eligibleEntries->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay participantes elegibles después de aplicar las reglas de la rifa. Verifica que haya invitados que cumplan todas las condiciones requeridas.'
                ];
            }

            // REGLA 1: Si este premio es el asignado a IMEX (imex_prize_id), DEBE ganar un IMEX
            // Solo aplica para RIFA PÚBLICA
            if ($raffleType === 'public') {
                // Verificar si este premio es el seleccionado para tener ganador IMEX
                // (ya se calculó arriba en la sección de rifa pública: $isIMEXPrize y $existingIMEXWinnerInThisPrize)
                
                // Solo forzar IMEX si:
                // 1. Este premio es el asignado a IMEX (imex_prize_id)
                // 2. Este premio aún no tiene ganador IMEX
                $needsIMEXWinner = $isIMEXPrize && !$existingIMEXWinnerInThisPrize;

                
                if ($needsIMEXWinner) {
                    // Buscar participantes IMEX elegibles en las entradas pendientes originales
                    // (antes de aplicar el filtro de Automovil, para tener mejor diagnóstico)
                    $imexEntriesInPending = $pendingEntries->filter(function ($entry) {
                        return $entry->guest->compania === 'IMEX';
                    });
                    
                    // Ahora buscar en las entradas elegibles (después de filtros)
                    $imexEntries = $eligibleEntries->filter(function ($entry) {
                        return $entry->guest->compania === 'IMEX';
                    });

                    if ($imexEntries->isEmpty()) {
                        // Si hay IMEX en pendientes pero no en elegibles, significa que fueron filtrados
                        if ($imexEntriesInPending->isNotEmpty() && $imexEntries->isEmpty()) {
                            return [
                                'success' => false,
                                'error' => 'REGLA 1: Hay participantes IMEX disponibles, pero fueron excluidos por otras reglas. Verifica las condiciones del premio y los participantes.'
                            ];
                        }
                        
                        // REGLA 1 es obligatoria: si no hay participantes IMEX disponibles, no se puede realizar el sorteo
                        return [
                            'success' => false,
                            'error' => 'REGLA 1: No hay participantes IMEX disponibles en las entradas pendientes. Debe haber al menos un ganador con compañía IMEX en el evento. Total de entradas pendientes: ' . $pendingEntries->count()
                        ];
                    } else {
                        // Asegurar que al menos un ganador sea IMEX
                        $imexWinner = $imexEntries->random(1)->first();
                        $winners = collect([$imexWinner]);
                        
                        // Si necesitamos más ganadores, seleccionar del resto de elegibles
                        if ($winnersCount > 1) {
                            $remainingEligible = $eligibleEntries->reject(function ($entry) use ($imexWinner) {
                                return $entry->id === $imexWinner->id;
                            });
                            
                            if ($remainingEligible->isNotEmpty()) {
                                $additionalWinners = $remainingEligible->random(min($winnersCount - 1, $remainingEligible->count()));
                                $winners = $winners->merge($additionalWinners);
                            }
                        }
                        
                        $winnerIds = $winners->pluck('id')->toArray();
                    }
                }
            }
            
            // Si no se aplicó la lógica de garantizar IMEX, hacer sorteo normal
            if (!isset($winnerIds)) {
                // Perform random draw from eligible entries
                $winners = $eligibleEntries->random(min($winnersCount, $eligibleEntries->count()));
                $winnerIds = $winners->pluck('id')->toArray();
            }

            // Mark winners
            $winnerEntries = [];
            foreach ($winners as $winnerEntry) {
                $winnerEntry->update([
                    'status' => 'won',
                    'drawn_at' => now(),
                ]);

                // Refresh the model to ensure all relationships are loaded
                $winnerEntry->refresh();

                // Decrement stock for each winner
                $prize->decrementStock(1);

                // Crear log del ganador (confirmado directamente)
                $this->createRaffleLog($prize, $winnerEntry, $raffleType, true);

                $winnerEntries[] = $winnerEntry;

                // Send winner notification email if requested
                if ($sendNotification && $winnerEntry->guest && $winnerEntry->guest->email) {
                    SendEmailJob::dispatch('raffle_winner', $winnerEntry->guest, null, [
                        'prize' => $prize
                    ]);
                }
            }

            // Mark losers
            RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->whereNotIn('id', $winnerIds)
                ->update([
                    'status' => 'lost',
                    'drawn_at' => now(),
                    'raffle_metadata' => json_encode([
                        'draw_timestamp' => now()->toISOString(),
                        'total_participants' => $pendingEntries->count()
                    ])
                ]);

            DB::commit();

            return [
                'success' => true,
                'winners' => $winnerEntries,
                'total_participants' => $pendingEntries->count(),
                'draw_timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Raffle draw failed', [
                'prize_id' => $prize->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Select a winner for a raffle without saving to database (temporary selection)
     * @param Prize $prize
     * @param string $raffleType Tipo de rifa: 'public' o 'general' (default: 'general')
     * @return array
     */
    public function selectWinnerTemporary(Prize $prize, string $raffleType = 'public'): array
    {
        try {
            // Get all pending entries for this prize
            $pendingEntries = RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->with('guest')
                ->get();

            if ($pendingEntries->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay participaciones pendientes para este premio'
                ];
            }

            // Refrescar el premio para obtener el stock actualizado
            $prize->refresh();
            
            // CORRECCIÓN AUTOMÁTICA: Si el stock es 0 pero no hay ganadores registrados,
            // restaurar el stock automáticamente (corrige datos corrompidos)
            if ($prize->stock <= 0) {
                $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
                if ($winnersCount === 0) {
                    // No hay ganadores registrados pero el stock es 0 - restaurar stock
                    $prize->restoreStockFromWinners();
                    $prize->refresh();
                    Log::info('Stock restaurado automáticamente para premio sin ganadores (selectWinnerTemporary)', [
                        'prize_id' => $prize->id,
                        'prize_name' => $prize->name,
                        'restored_stock' => $prize->stock
                    ]);
                }
            }
            
            // Check if we have enough stock
            $availableStock = max($prize->stock, 0);
            
            if ($availableStock <= 0) {
                return [
                    'success' => false,
                    'error' => 'No hay stock disponible para este premio. Stock actual: ' . $prize->stock
                ];
            }

            // Aplicar reglas según el tipo de rifa
            $eligibleEntries = $pendingEntries;
            $existingIMEXWinner = false;
            
            // REGLAS DE RIFA PÚBLICA (solo para rifa pública)
            if ($raffleType === 'public') {
                // Obtener el evento y verificar/establecer el premio IMEX aleatorio
                $event = $prize->event;
                
                // Si no hay premio IMEX seleccionado, seleccionarlo aleatoriamente
                if (!$event->imex_prize_id) {
                    // Obtener todos los premios activos del evento donde IMEX puede participar
                    // (excluyendo "Automovil" y "Rifa General")
                    $eligiblePrizesForIMEX = Prize::where('event_id', $event->id)
                        ->where('active', true)
                        ->where('name', '!=', 'Rifa General')
                        ->whereRaw('LOWER(name) != ?', ['automovil'])
                        ->get();
                    
                    if ($eligiblePrizesForIMEX->isNotEmpty()) {
                        // Seleccionar aleatoriamente un premio
                        $selectedIMEXPrize = $eligiblePrizesForIMEX->random();
                        $event->imex_prize_id = $selectedIMEXPrize->id;
                        $event->save();
                    }
                }
                
                // REGLA 1: Debe haber si o si 1 ganador con la Compañía IMEX (obligatorio)
                // Verificar si este premio es el seleccionado para tener ganador IMEX
                $isIMEXPrize = $event->imex_prize_id === $prize->id;
                
                // Verificar si ya existe un ganador IMEX en este premio específico
                $existingIMEXWinnerInThisPrize = RaffleEntry::where('prize_id', $prize->id)
                    ->whereHas('guest', function ($q) {
                        $q->where('compania', 'IMEX');
                    })
                    ->where('status', 'won')
                    ->exists();
                
                // Filtrar candidatos según reglas adicionales de rifa pública
                $eligibleEntries = $pendingEntries->filter(function ($entry) use ($prize, $isIMEXPrize, $existingIMEXWinnerInThisPrize) {
                    $guest = $entry->guest;
                    
                    // REGLA 4: Un Guest no puede GANAR más de un Prize (RIFA PÚBLICA)
                    // EXCEPCIÓN: El Automóvil puede ser ganado aunque ya se haya ganado otro premio
                    if (strtolower($prize->name) !== 'automovil') {
                        $hasWonOtherPrize = RaffleEntry::where('guest_id', $guest->id)
                            ->where('prize_id', '!=', $prize->id)
                            ->where('status', 'won')
                            ->exists();
                        
                        if ($hasWonOtherPrize) {
                            return false; // Ya ganó otro premio, no puede ganar este
                        }
                    }
                    
                    // REGLA ESPECIAL: Si este premio es el imex_prize_id, SOLO IMEX pueden ganar
                    if ($isIMEXPrize) {
                        // Solo permitir IMEX para este premio
                        if ($guest->compania !== 'IMEX') {
                            return false;
                        }
                        // Si ya tiene ganador IMEX, excluir otros IMEX
                        if ($existingIMEXWinnerInThisPrize) {
                            return false;
                        }
                        return true; // IMEX puede ganar este premio
                    }
                    
                    // Para premios que NO son el imex_prize_id:
                    // IMEX puede aparecer en la animación pero NO puede ganar
                    if ($guest->compania === 'IMEX') {
                        // Excluir IMEX de poder ganar (pero mantener en entradas para animación)
                        return false;
                    }

                    // REGLA 2: Si Compañía del Guest es IMEX no puede ganar Prize con name Automovil
                    // (ya se excluyó arriba, pero por seguridad)
                    if (strtolower($prize->name) === 'automovil' && $guest->compania === 'IMEX') {
                        return false;
                    }

                    // REGLA 11: Si Descripción del Guest es Subdirectores no puede ganar prize con name Automovil
                    if (strtolower($prize->name) === 'automovil' && $guest->descripcion === 'Subdirectores') {
                        return false;
                    }

                    return true;
                });
            }

            if ($eligibleEntries->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay participantes elegibles después de aplicar las reglas de la rifa. Verifica que haya invitados que cumplan todas las condiciones requeridas.'
                ];
            }

            // REGLA 1: Si este premio es el asignado a IMEX (imex_prize_id), DEBE ganar un IMEX
            // Solo aplica para RIFA PÚBLICA
            if ($raffleType === 'public') {
                // Verificar si este premio es el seleccionado para tener ganador IMEX
                // (ya se calculó arriba en la sección de rifa pública: $isIMEXPrize y $existingIMEXWinnerInThisPrize)
                
                // Solo forzar IMEX si:
                // 1. Este premio es el asignado a IMEX (imex_prize_id)
                // 2. Este premio aún no tiene ganador IMEX
                $needsIMEXWinner = $isIMEXPrize && !$existingIMEXWinnerInThisPrize;
                
                if ($needsIMEXWinner) {
                    // En este punto, $eligibleEntries ya solo contiene IMEX (filtrado arriba)
                    $imexEntries = $eligibleEntries;

                    if ($imexEntries->isEmpty()) {
                        return [
                            'success' => false,
                            'error' => 'REGLA 1: No hay participantes IMEX disponibles en las entradas pendientes. Debe haber al menos un ganador con compañía IMEX en el evento. Total de entradas pendientes: ' . $pendingEntries->count()
                        ];
                    } else {
                        // Asegurar que el ganador sea IMEX
                        $winnerEntry = $imexEntries->random(1)->first();
                    }
                } else {
                    // Sorteo normal
                    $winnerEntry = $eligibleEntries->random(1)->first();
                }
            } else {
                // Sorteo normal para rifa general
                $winnerEntry = $eligibleEntries->random(1)->first();
            }

            // Crear log de la selección (aún no confirmado)
            $this->createRaffleLog($prize, $winnerEntry, $raffleType, false);

            // NO guardar en BD, solo devolver el ganador seleccionado
            return [
                'success' => true,
                'winner' => $winnerEntry,
                'total_participants' => $pendingEntries->count(),
                'draw_timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Temporary winner selection failed', [
                'prize_id' => $prize->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirm and save a previously selected winner to database
     * @param Prize $prize
     * @param int $entryId The raffle entry ID of the winner
     * @param bool $sendNotification
     * @return array
     */
    public function confirmWinner(Prize $prize, int $entryId, bool $sendNotification = true): array
    {
        try {
            DB::beginTransaction();

            // Find the entry
            $winnerEntry = RaffleEntry::where('prize_id', $prize->id)
                ->where('id', $entryId)
                ->where('status', 'pending')
                ->with('guest')
                ->first();

            if (!$winnerEntry) {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => 'No se encontró la participación del ganador o ya fue procesada'
                ];
            }

            // Mark as winner
            $winnerEntry->update([
                'status' => 'won',
                'drawn_at' => now(),
            ]);

            $winnerEntry->refresh();

            // Decrement stock
            $prize->decrementStock(1);

            // Marcar el log más reciente de este ganador como confirmado
            // O crear uno nuevo si no existe
            $raffleType = $prize->name === 'Rifa General' ? 'general' : 'public';
            $log = RaffleLog::where('prize_id', $prize->id)
                ->where('guest_id', $winnerEntry->guest_id)
                ->where('confirmed', false)
                ->latest()
                ->first();
            
            if ($log) {
                $log->update(['confirmed' => true]);
            } else {
                // Si no hay log previo, crear uno confirmado
                $this->createRaffleLog($prize, $winnerEntry, $raffleType, true);
            }

            // Mark other entries as losers
            RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->where('id', '!=', $entryId)
                ->update([
                    'status' => 'lost',
                    'drawn_at' => now(),
                ]);

            // Send winner notification email if requested
            if ($sendNotification && $winnerEntry->guest && $winnerEntry->guest->email) {
                SendEmailJob::dispatch('raffle_winner', $winnerEntry->guest, null, [
                    'prize' => $prize
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'winner' => $winnerEntry,
                'draw_timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Confirm winner failed', [
                'prize_id' => $prize->id,
                'entry_id' => $entryId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get eligible guests for a prize
     * @param Prize $prize
     * @param string $raffleType Tipo de rifa: 'public' o 'general' (default: 'general')
     * @return Collection
     */
    public function getEligibleGuests(Prize $prize, string $raffleType = 'general'): Collection
    {
        $query = Guest::where('event_id', $prize->event_id);

        // REGLAS COMUNES PARA AMBOS TIPOS DE RIFA
        // REGLA 9: Si el Guest no tiene attendance registrada no puede participar
        $query->whereHas('attendance');

        // REGLAS ESPECÍFICAS DE RIFA PÚBLICA
        if ($raffleType === 'public') {
            $event = $prize->event;
            $isIMEXPrize = $event->imex_prize_id === $prize->id;
            
            // REGLA 10: Si Compañía del Guest es INV no puede participar
            $query->where('compania', '!=', 'INV');

            // REGLA 3: Si Descripción del Guest es General puede participar en la rifa
            // REGLA 5: Si Descripción del Guest es Subdirectores puede participar
            // REGLA 6: Si Descripción del Guest es Ganadores previos no puede participar
            // REGLA 7: Si Descripción del Guest es Nuevo ingreso no puede participar
            // REGLA 8: Si Descripción del Guest es Directores no puede participar
            // Aplicar: excluir los que NO pueden participar, permitir los demás (incluyendo null o otros valores)
            $query->whereNotIn('descripcion', ['Ganadores previos', 'Nuevo ingreso', 'Directores']);

            // REGLA ESPECIAL: Para el premio imex_prize_id, todos pueden participar (aparecer en animación)
            // pero solo IMEX puede ganar (eso se maneja en drawRaffle)
            // Para el automóvil, excluir IMEX completamente (ni participar ni aparecer en animación)
            if (strtolower($prize->name) === 'automovil') {
                $query->where(function ($q) {
                    $q->where('compania', '!=', 'IMEX')
                      ->where('descripcion', '!=', 'Subdirectores');
                });
            }
            // Para otros premios (incluyendo imex_prize_id), todos pueden participar
            // pero en drawRaffle se filtra que solo IMEX pueda ganar el imex_prize_id

            // REGLA 4: Un Guest no puede GANAR más de un Prize (RIFA PÚBLICA)
            // Excluir guests que ya GANARON un prize en otro premio (pueden participar, pero no ganar)
            $query->whereDoesntHave('raffleEntries', function ($q) use ($prize) {
                $q->where('prize_id', '!=', $prize->id)
                  ->where('status', 'won'); // Solo excluir si ya GANÓ, no si tiene pendiente
            });
        }
        
        // REGLAS DE RIFA GENERAL
        if ($raffleType === 'general') {
            // REGLA CRÍTICA: Los ganadores de RIFA PÚBLICA no pueden participar en RIFA GENERAL
            // Excluir guests que ya ganaron en CUALQUIER premio de rifa pública del evento
            $query->whereDoesntHave('raffleEntries', function ($q) use ($prize) {
                $q->where('event_id', $prize->event_id)
                  ->where('status', 'won')
                  // Excluir premios de rifa general (solo contar ganadores de rifa pública)
                  ->whereHas('prize', function ($pq) {
                      $pq->where('name', '!=', 'Rifa General');
                  });
            });
        }

        return $query->get();
    }

    /**
     * Get raffle statistics for an event
     */
    public function getRaffleStatistics(Event $event): array
    {
        // Excluir el premio especial "Rifa General" del conteo
        // Usar whereRaw para comparación case-insensitive y sin espacios
        $totalPrizes = $event->prizes()
            ->whereRaw("TRIM(name) != 'Rifa General'")
            ->count();
        $activePrizes = $event->prizes()
            ->where('active', true)
            ->whereRaw("TRIM(name) != 'Rifa General'")
            ->count();
        $totalStock = $event->prizes()
            ->whereRaw("TRIM(name) != 'Rifa General'")
            ->sum('stock');
        
        // Contar todas las entradas (para referencia)
        $totalEntries = $event->raffleEntries()->count();
        $pendingEntries = $event->raffleEntries()->where('status', 'pending')->count();
        
        // Contar participantes únicos (unique guest_id) que tienen al menos una entrada
        $uniqueParticipants = (int) DB::table('raffle_entries')
            ->where('event_id', $event->id)
            ->selectRaw('COUNT(DISTINCT guest_id) as count')
            ->first()
            ->count ?? 0;
        
        // Contar ganadores únicos (unique guest_id) con status='won' para evitar duplicados
        // Excluir ganadores de la rifa general (premio especial "Rifa General")
        $generalPrizeId = Prize::where('event_id', $event->id)
            ->where('name', 'Rifa General')
            ->value('id');
        
        $uniqueWinnersQuery = DB::table('raffle_entries')
            ->where('event_id', $event->id)
            ->where('status', 'won');
        
        if ($generalPrizeId) {
            $uniqueWinnersQuery->where('prize_id', '!=', $generalPrizeId);
        }
        
        $uniqueWinners = (int) $uniqueWinnersQuery
            ->selectRaw('COUNT(DISTINCT guest_id) as count')
            ->first()
            ->count ?? 0;
        
        $prizesByCategory = $event->prizes()
            ->whereRaw("TRIM(name) != 'Rifa General'")
            ->selectRaw('category, COUNT(*) as count, SUM(stock) as total_stock')
            ->groupBy('category')
            ->get();

        // Calcular premios disponibles (premios que aún no se han rifado)
        // Excluir el premio especial "Rifa General"
        $availableStock = $event->prizes()
            ->where('active', true)
            ->whereRaw("TRIM(name) != 'Rifa General'")
            ->get()
            ->sum(function ($prize) {
                $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
                return max(0, $prize->stock - $winnersCount);
            });

        // Calcular premios entregados y porcentaje
        // Premios entregados = Total de premios - Stock disponible actual
        // Como cada premio se crea con stock=1, el stock inicial total = total_prizes
        // Premios entregados = total_prizes - total_stock
        $deliveredPrizes = max(0, $totalPrizes - $totalStock);
        $deliveredPercentage = $totalPrizes > 0 
            ? round(($deliveredPrizes / $totalPrizes) * 100, 1) 
            : 0;

        return [
            'total_prizes' => $totalPrizes,
            'active_prizes' => $activePrizes,
            'total_stock' => $totalStock,
            'available_stock' => $availableStock,
            'delivered_prizes' => $deliveredPrizes,
            'delivered_percentage' => $deliveredPercentage,
            'total_entries' => $uniqueParticipants, // Cambiado: ahora muestra participantes únicos
            'total_winners' => $uniqueWinners, // Cambiado: ahora muestra ganadores únicos
            'total_guests' => $event->guests()->count(),
            'attended_guests' => $event->guests()->whereHas('attendance')->count(),
            'prizes_by_category' => $prizesByCategory->map(function ($category) {
                return [
                    $category->category => $category->count
                ];
            })->pluck(0, 'category')->toArray()
        ];
    }

    /**
     * Get detailed raffle results for a prize
     */
    public function getPrizeResults(Prize $prize): array
    {
        $entries = $prize->raffleEntries()
            ->with(['guest'])
            ->orderBy('drawn_at', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $winners = $entries->where('status', 'won');
        $losers = $entries->where('status', 'lost');
        $pending = $entries->where('status', 'pending');

        return [
            'total_entries' => $entries->count(),
            'winners_count' => $winners->count(),
            'pending_entries' => $pending->count(),
            'losers_count' => $losers->count(),
            'stock_remaining' => $prize->stock,
            'is_complete' => $pending->isEmpty()
        ];
    }

    /**
     * Cancel raffle and reset all entries
     */
    public function cancelRaffle(Prize $prize): array
    {
        try {
            DB::beginTransaction();

            // Get count before deletion
            $entriesCount = $prize->raffleEntries()->count();
            
            // Reset stock for winners
            $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
            $prize->increment('stock', $winnersCount);

            // Delete all raffle entries
            $prize->raffleEntries()->delete();

            DB::commit();

            return [
                'success' => true,
                'cancelled' => $entriesCount,
                'stock_restored' => $winnersCount
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to cancel raffle', [
                'prize_id' => $prize->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Notify a winner via email
     */
    public function notifyWinner(RaffleEntry $entry): void
    {
        if ($entry->guest->email && $entry->prize) {
            SendEmailJob::dispatch('raffle_winner', $entry->guest, null, [
                'prize' => $entry->prize
            ]);
        }
    }

    /**
     * Validate prize configuration
     */
    public function validatePrize(Prize $prize): array
    {
        $messages = [];

        if ($prize->stock <= 0) {
            $messages[] = 'El premio no tiene stock disponible';
        }

        if (!$prize->active) {
            $messages[] = 'El premio no está activo';
        }

        // Por defecto usar 'general' para validación (puedes cambiar esto según necesites)
        $eligibleGuests = $this->getEligibleGuests($prize, 'general');
        if ($eligibleGuests->isEmpty()) {
            $messages[] = 'No hay invitados elegibles para este premio';
        }

        $canRaffle = $prize->stock > 0 && $prize->active && $eligibleGuests->isNotEmpty();

        // Si puede rifar, agregar mensaje positivo
        if ($canRaffle) {
            $messages = [
                "✓ El premio está listo para rifar",
                "✓ Stock disponible: {$prize->stock}",
                "✓ Invitados elegibles: {$eligibleGuests->count()}"
            ];
        }

        return [
            'can_raffle' => $canRaffle,
            'messages' => $messages,
            'eligible_count' => $eligibleGuests->count()
        ];
    }

    /**
     * Obtener o crear el premio especial "Rifa General" para un evento
     * @param Event $event
     * @return Prize
     */
    public function getOrCreateGeneralRafflePrize(Event $event): Prize
    {
        return Prize::firstOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'Rifa General',
            ],
            [
                'description' => 'Premio especial para la rifa general. No es un premio físico, solo identifica las participaciones de la rifa general.',
                'category' => 'Rifa General',
                'stock' => 999, // Stock alto para permitir múltiples ganadores
                'value' => null,
                'active' => true,
            ]
        );
    }

    /**
     * Obtener invitados elegibles para la rifa general
     * @param Event $event
     * @return Collection
     */
    public function getEligibleGuestsForGeneralRaffle(Event $event): Collection
    {
        $query = Guest::where('event_id', $event->id);

        // REGLA 8: Si el Guest no tiene attendance registrada no puede participar
        $query->whereHas('attendance');

        // REGLA 3: Si Descripción del Guest es "General" puede participar en la rifa
        // REGLA 3b: Si Descripción del Guest es "Subdirectores" también puede participar
        // Solo los que tienen descripción "General" o "Subdirectores" pueden participar
        $query->whereIn('descripcion', ['General', 'Subdirectores', 'IMEX']);

        // REGLA 9: Si Compañía del Guest es "INV" no puede participar
        $query->where('compania', '!=', 'INV');

        // REGLA 5: Si Descripción del Guest es "Ganadores previos" no puede participar
        // (Ya excluido por la regla 3, pero por seguridad)
        $query->where('descripcion', '!=', 'Ganadores previos');

        // REGLA 6: Si Descripción del Guest es "Nuevo ingreso" no puede participar
        // (Ya excluido por la regla 3, pero por seguridad)
        $query->where('descripcion', '!=', 'Nuevo ingreso');

        // REGLA 7: Si Descripción del Guest es "Directores" no puede participar
        // (Ya excluido por la regla 3, pero por seguridad)
        $query->where('descripcion', '!=', 'Directores');

        // REGLA 2: No pueden participar ganadores de la Rifa Pública
        // Un ganador de la rifa pública es alguien que ganó en cualquier premio que no sea "Rifa General"
        // Los ganadores de la rifa general pueden volver a participar si se borran
        $generalPrizeId = $this->getOrCreateGeneralRafflePrize($event)->id;
        $query->whereDoesntHave('raffleEntries', function ($q) use ($generalPrizeId) {
            $q->where('prize_id', '!=', $generalPrizeId) // Excluir solo premios que NO sean "Rifa General"
              ->where('status', 'won'); // Excluir guests que ya ganaron en rifa pública (solo status 'won')
        });

        return $query->get();
    }

    /**
     * Crear entradas para la rifa general (usando premio especial "Rifa General")
     * @param Event $event
     * @return array
     */
    public function createGeneralRaffleEntries(Event $event): array
    {
        try {
            DB::beginTransaction();

            // Obtener o crear el premio especial "Rifa General"
            $generalPrize = $this->getOrCreateGeneralRafflePrize($event);

            $eligibleGuests = $this->getEligibleGuestsForGeneralRaffle($event);
            
            if ($eligibleGuests->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay invitados elegibles para la rifa general.',
                    'entries_created' => 0,
                    'total_eligible' => 0
                ];
            }

            $entriesCreated = 0;
            $alreadyHasEntry = 0;

            foreach ($eligibleGuests as $guest) {
                // Verificar si el guest ya tiene una entrada para la rifa general
                $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                    ->where('event_id', $event->id)
                    ->where('prize_id', $generalPrize->id)
                    ->first();

                if ($existingEntry) {
                    $alreadyHasEntry++;
                    continue;
                }

                // Crear entrada con el premio especial "Rifa General"
                RaffleEntry::enterRaffle($guest, $generalPrize, [
                    'auto_entered' => true,
                    'entered_by' => 'system',
                    'raffle_type' => 'general'
                ]);
                $entriesCreated++;
            }

            DB::commit();

            return [
                'success' => true,
                'entries_created' => $entriesCreated,
                'already_has_entry' => $alreadyHasEntry,
                'total_eligible' => $eligibleGuests->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear entradas para rifa general', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error al crear entradas: ' . $e->getMessage(),
                'entries_created' => 0
            ];
        }
    }

    /**
     * Realizar el sorteo de la rifa general
     * @param Event $event
     * @param int $winnersCount Número de ganadores a seleccionar
     * @param bool $sendNotification
     * @return array
     */
    public function drawGeneralRaffle(Event $event, int $winnersCount, bool $sendNotification = false): array
    {
        try {
            DB::beginTransaction();

            // Obtener o crear el premio especial "Rifa General"
            $generalPrize = $this->getOrCreateGeneralRafflePrize($event);

            // Asegurar que todos los elegibles tengan entradas creadas
            // Esto garantiza que si se borraron ganadores, todos los elegibles puedan participar
            $createResult = $this->createGeneralRaffleEntries($event);
            if (!$createResult['success'] && $createResult['total_eligible'] > 0) {
                // Si hay elegibles pero no se pudieron crear entradas, es un error
                return [
                    'success' => false,
                    'error' => 'Error al crear entradas: ' . ($createResult['error'] ?? 'Error desconocido')
                ];
            }
            
            // Obtener entradas pendientes de la rifa general (usando el premio especial)
            $pendingEntries = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'pending')
                ->with('guest')
                ->get();

            $eligibleEntries = $pendingEntries;

            if ($eligibleEntries->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No hay participantes elegibles después de aplicar las reglas.'
                ];
            }

            // REGLA 1: Debe haber solo 2 ganadores con la Compañía IMEX (por evento)
            $existingInexWinnersCount = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'won')
                ->whereHas('guest', function ($q) {
                    $q->where('compania', 'IMEX');
                })
                ->count();
            
            $imexEntries = $eligibleEntries->filter(function ($entry) {
                $compania = strtoupper(trim($entry->guest->compania ?? ''));
                return $compania === 'IMEX';
            });
            
            $nonInexEntries = $eligibleEntries->filter(function ($entry) {
                $compania = strtoupper(trim($entry->guest->compania ?? ''));
                return $compania !== 'IMEX';
            });

            $winners = collect();
            $maxInexWinners = 2;
            $winnersCount = min($winnersCount, $eligibleEntries->count());
            $imexSlotsAvailable = $maxInexWinners - $existingInexWinnersCount;
            
            // REGLA 1: Seleccionar primero los ganadores IMEX (hasta 2, independientemente de cuántos ganadores totales se necesiten)
            if ($imexEntries->isNotEmpty() && $imexSlotsAvailable > 0) {
                // Seleccionar hasta los slots disponibles (máximo 2) o los que haya disponibles
                $imexWinnersToSelect = min($imexSlotsAvailable, $imexEntries->count());
                
                if ($imexWinnersToSelect > 0) {
                    $imexWinners = $imexEntries->shuffle()->take($imexWinnersToSelect);
                    $winners = $winners->merge($imexWinners);
                }
            }

            // Completar el resto de ganadores con no-IMEX
            $remainingWinnersNeeded = $winnersCount - $winners->count();
            if ($remainingWinnersNeeded > 0 && $nonInexEntries->isNotEmpty()) {
                $nonInexWinners = $nonInexEntries->shuffle()->take($remainingWinnersNeeded);
                $winners = $winners->merge($nonInexWinners);
            }

            // Marcar ganadores (no decrementar stock del premio especial)
            foreach ($winners as $entry) {
                $entry->update([
                    'status' => 'won',
                    'drawn_at' => Carbon::now(),
                    'raffle_metadata' => array_merge($entry->raffle_metadata ?? [], [
                        'raffle_type' => 'general',
                        'drawn_at' => now()
                    ]),
                ]);

                // Crear log del ganador (confirmado directamente)
                $this->createRaffleLog($generalPrize, $entry, 'general', true);
            }

            // Marcar el resto como perdedores
            $losers = $eligibleEntries->diff($winners);
            foreach ($losers as $entry) {
                $entry->markAsLoser([
                    'raffle_type' => 'general'
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'winners' => $winners,
                'winners_count' => $winners->count(),
                'total_eligible' => $eligibleEntries->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al realizar rifa general', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error al realizar la rifa: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear un log de rifa cuando se selecciona un ganador
     * @param Prize $prize
     * @param RaffleEntry $winnerEntry
     * @param string $raffleType
     * @param bool $confirmed
     * @return RaffleLog
     */
    private function createRaffleLog(Prize $prize, RaffleEntry $winnerEntry, string $raffleType = 'public', bool $confirmed = false): RaffleLog
    {
        return RaffleLog::create([
            'event_id' => $prize->event_id,
            'user_id' => Auth::id(),
            'prize_id' => $prize->id,
            'guest_id' => $winnerEntry->guest_id,
            'raffle_type' => $raffleType,
            'confirmed' => $confirmed,
        ]);
    }
}