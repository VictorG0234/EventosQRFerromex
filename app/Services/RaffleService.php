<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RaffleService
{
    /**
     * Create raffle entries for all eligible guests for a specific prize
     */
    public function createRaffleEntries(Prize $prize): array
    {
        try {
            DB::beginTransaction();

            $eligibleGuests = $this->getEligibleGuests($prize);
            $entriesCreated = 0;

            foreach ($eligibleGuests as $guest) {
                // Check if guest already has an entry for this prize
                $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                    ->where('prize_id', $prize->id)
                    ->first();

                if (!$existingEntry) {
                    RaffleEntry::enterRaffle($guest, $prize, [
                        'auto_entered' => true,
                        'entered_by' => 'system'
                    ]);
                    $entriesCreated++;
                }
            }

            DB::commit();

            Log::info('Raffle entries created', [
                'prize_id' => $prize->id,
                'entries_created' => $entriesCreated,
                'total_eligible' => $eligibleGuests->count()
            ]);

            return [
                'success' => true,
                'entries_created' => $entriesCreated,
                'total_eligible' => $eligibleGuests->count(),
                'already_entered' => $eligibleGuests->count() - $entriesCreated
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
     */
    public function drawRaffle(Prize $prize, int $winnersCount = 1): array
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

            // Check if we have enough stock
            $winnersCount = min($winnersCount, $prize->stock);
            if ($winnersCount <= 0) {
                return [
                    'success' => false,
                    'error' => 'No hay stock disponible para este premio'
                ];
            }

            // Perform random draw
            $winners = $pendingEntries->random(min($winnersCount, $pendingEntries->count()));
            $winnerIds = $winners->pluck('id')->toArray();

            // Mark winners
            $winnerEntries = [];
            foreach ($winners as $winnerEntry) {
                $winnerEntry->markAsWinner([
                    'draw_timestamp' => now()->toISOString(),
                    'total_participants' => $pendingEntries->count(),
                    'winner_position' => array_search($winnerEntry->id, $winnerIds) + 1
                ]);

                $winnerEntries[] = [
                    'entry' => $winnerEntry,
                    'guest' => $winnerEntry->guest,
                    'prize' => $prize
                ];

                // Send winner notification email
                if ($winnerEntry->guest->email) {
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

            Log::info('Raffle draw completed', [
                'prize_id' => $prize->id,
                'winners_count' => count($winnerEntries),
                'total_participants' => $pendingEntries->count()
            ]);

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
     * Get eligible guests for a prize
     */
    public function getEligibleGuests(Prize $prize): Collection
    {
        return Guest::where('event_id', $prize->event_id)
            ->whereHas('attendance') // Must have attended
            ->where(function ($query) use ($prize) {
                // Must be eligible for this prize category
                $query->whereJsonContains('premios_rifa', $prize->category)
                    ->orWhere('premios_rifa', 'like', '%' . $prize->category . '%');
            })
            ->get();
    }

    /**
     * Get raffle statistics for an event
     */
    public function getRaffleStatistics(Event $event): array
    {
        $totalPrizes = $event->prizes()->count();
        $activePrizes = $event->prizes()->where('active', true)->count();
        $totalStock = $event->prizes()->sum('stock');
        $initialStock = $event->prizes()->sum('initial_stock');
        
        $totalEntries = $event->raffleEntries()->count();
        $pendingEntries = $event->raffleEntries()->where('status', 'pending')->count();
        $winners = $event->raffleEntries()->where('status', 'won')->count();
        
        $prizesByCategory = $event->prizes()
            ->selectRaw('category, COUNT(*) as count, SUM(stock) as total_stock')
            ->groupBy('category')
            ->get();

        return [
            'overview' => [
                'total_prizes' => $totalPrizes,
                'active_prizes' => $activePrizes,
                'total_stock' => $totalStock,
                'distributed_stock' => $initialStock - $totalStock,
                'distribution_rate' => $initialStock > 0 ? round((($initialStock - $totalStock) / $initialStock) * 100, 2) : 0
            ],
            'participation' => [
                'total_entries' => $totalEntries,
                'pending_entries' => $pendingEntries,
                'total_winners' => $winners,
                'completion_rate' => $totalEntries > 0 ? round((($totalEntries - $pendingEntries) / $totalEntries) * 100, 2) : 0
            ],
            'categories' => $prizesByCategory->map(function ($category) {
                return [
                    'name' => $category->category,
                    'prizes_count' => $category->count,
                    'total_stock' => $category->total_stock
                ];
            }),
            'eligible_guests' => $event->guests()
                ->whereHas('attendance')
                ->whereNotNull('premios_rifa')
                ->count()
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
            'prize' => $prize,
            'summary' => [
                'total_entries' => $entries->count(),
                'winners' => $winners->count(),
                'losers' => $losers->count(),
                'pending' => $pending->count(),
                'stock_remaining' => $prize->stock,
                'is_complete' => $pending->isEmpty()
            ],
            'winners_list' => $winners->map(function ($entry) {
                return [
                    'guest_name' => $entry->guest->full_name,
                    'employee_number' => $entry->guest->numero_empleado,
                    'won_at' => $entry->drawn_at,
                    'metadata' => $entry->raffle_metadata
                ];
            }),
            'participation_timeline' => $entries->groupBy(function ($entry) {
                return $entry->created_at->format('Y-m-d H:00');
            })->map(function ($group) {
                return $group->count();
            })
        ];
    }

    /**
     * Cancel raffle and reset all entries
     */
    public function cancelRaffle(Prize $prize): bool
    {
        try {
            DB::beginTransaction();

            // Reset stock for winners
            $winnersCount = $prize->raffleEntries()->where('status', 'won')->count();
            $prize->increment('stock', $winnersCount);

            // Delete all raffle entries
            $prize->raffleEntries()->delete();

            DB::commit();

            Log::info('Raffle cancelled', [
                'prize_id' => $prize->id,
                'entries_deleted' => $prize->raffleEntries()->count(),
                'stock_restored' => $winnersCount
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to cancel raffle', [
                'prize_id' => $prize->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate prize configuration
     */
    public function validatePrize(Prize $prize): array
    {
        $errors = [];

        if ($prize->stock <= 0) {
            $errors[] = 'El premio no tiene stock disponible';
        }

        if (!$prize->active) {
            $errors[] = 'El premio no está activo';
        }

        $eligibleGuests = $this->getEligibleGuests($prize);
        if ($eligibleGuests->isEmpty()) {
            $errors[] = 'No hay invitados elegibles para este premio';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'eligible_count' => $eligibleGuests->count()
        ];
    }
}