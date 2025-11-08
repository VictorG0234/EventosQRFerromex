<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Prize;
use App\Models\Guest;
use App\Models\RaffleEntry;
use App\Services\RaffleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class RaffleController extends Controller
{
    protected $raffleService;

    public function __construct(RaffleService $raffleService)
    {
        $this->raffleService = $raffleService;
    }

    /**
     * Display the main raffle interface.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $prizes = $event->prizes()
            ->where('active', true)
            ->withCount(['raffleEntries', 'raffleEntries as winners_count' => function ($query) {
                $query->where('status', 'won');
            }])
            ->get()
            ->map(function ($prize) {
                $eligibleCount = $this->raffleService->getEligibleGuests($prize)->count();
                return [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'category' => $prize->category,
                    'stock' => $prize->stock,
                    'value' => $prize->value,
                    'image' => $prize->image,
                    'participants_count' => $prize->raffle_entries_count,
                    'winners_count' => $prize->winners_count,
                    'eligible_count' => $eligibleCount,
                    'remaining_stock' => $prize->stock - $prize->winners_count,
                    'is_available' => $prize->isAvailable(),
                    'can_raffle' => $eligibleCount > 0 && $prize->isAvailable()
                ];
            });

        $statistics = $this->raffleService->getRaffleStatistics($event);

        return Inertia::render('Events/Raffle/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date
            ],
            'prizes' => $prizes,
            'statistics' => $statistics
        ]);
    }

    /**
     * Show raffle configuration for a specific prize.
     */
    public function show(Event $event, Prize $prize)
    {
        $this->authorize('view', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $eligibleGuests = $this->raffleService->getEligibleGuests($prize);
        $validation = $this->raffleService->validatePrize($prize);
        $results = $this->raffleService->getPrizeResults($prize);

        return Inertia::render('Events/Raffle/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prize' => [
                'id' => $prize->id,
                'name' => $prize->name,
                'description' => $prize->description,
                'category' => $prize->category,
                'stock' => $prize->stock,
                'value' => $prize->value,
                'image' => $prize->image,
                'stock_percentage' => $prize->getStockPercentage()
            ],
            'eligible_guests' => $eligibleGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->area_laboral,
                    'email' => $guest->email,
                    'attended_at' => $guest->attendance?->created_at->format('d/m/Y H:i')
                ];
            }),
            'validation' => $validation,
            'results' => $results
        ]);
    }

    /**
     * Create raffle entries for eligible guests.
     */
    public function createEntries(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        try {
            // Validate prize before creating entries
            $validation = $this->raffleService->validatePrize($prize);
            
            if (!$validation['can_raffle']) {
                return back()->with('error', $validation['messages'][0] ?? 'No se puede crear la rifa para este premio.');
            }

            $result = $this->raffleService->createRaffleEntries($prize);

            return back()->with('success', "Se crearon {$result['created']} participaciones para la rifa del premio \"{$prize->name}\".");
            
        } catch (Exception $e) {
            return back()->with('error', 'Error al crear las participaciones: ' . $e->getMessage());
        }
    }

    /**
     * Execute a raffle draw.
     */
    public function draw(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $prize->stock,
            'send_notification' => 'boolean'
        ]);

        try {
            $result = $this->raffleService->drawRaffle($prize, $validated['quantity'], $validated['send_notification'] ?? true);

            $winnerNames = collect($result['winners'])->pluck('guest.full_name')->implode(', ');
            $message = "¡Rifa realizada exitosamente! Ganadores: {$winnerNames}";

            if ($validated['send_notification'] ?? true) {
                $message .= ' (se enviarán notificaciones por email)';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'winners' => $result['winners']->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'guest' => [
                            'id' => $entry->guest->id,
                            'name' => $entry->guest->full_name,
                            'employee_number' => $entry->guest->numero_empleado,
                            'work_area' => $entry->guest->area_laboral,
                            'email' => $entry->guest->email
                        ],
                        'drawn_at' => $entry->drawn_at->format('d/m/Y H:i:s'),
                        'order' => $entry->order
                    ];
                }),
                'remaining_stock' => $prize->fresh()->stock - $result['winners']->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la rifa: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel a raffle (mark all entries as cancelled).
     */
    public function cancel(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        try {
            $result = $this->raffleService->cancelRaffle($prize);

            return back()->with('success', "Rifa cancelada exitosamente. Se cancelaron {$result['cancelled']} participaciones.");
            
        } catch (Exception $e) {
            return back()->with('error', 'Error al cancelar la rifa: ' . $e->getMessage());
        }
    }

    /**
     * Get raffle entries for a prize.
     */
    public function entries(Event $event, Prize $prize)
    {
        $this->authorize('view', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $entries = $prize->raffleEntries()
            ->with('guest')
            ->orderBy('status', 'desc')
            ->orderBy('drawn_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'guest' => [
                        'id' => $entry->guest->id,
                        'name' => $entry->guest->full_name,
                        'employee_number' => $entry->guest->numero_empleado,
                        'work_area' => $entry->guest->area_laboral,
                        'email' => $entry->guest->email
                    ],
                    'status' => $entry->status,
                    'drawn_at' => $entry->drawn_at?->format('d/m/Y H:i:s'),
                    'order' => $entry->order,
                    'created_at' => $entry->created_at->format('d/m/Y H:i:s')
                ];
            });

        return response()->json([
            'entries' => $entries,
            'summary' => [
                'total' => $entries->count(),
                'winners' => $entries->where('status', 'won')->count(),
                'pending' => $entries->where('status', 'pending')->count(),
                'cancelled' => $entries->where('status', 'cancelled')->count()
            ]
        ]);
    }

    /**
     * Reset a specific raffle entry (mark as pending).
     */
    public function resetEntry(Event $event, Prize $prize, RaffleEntry $entry)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id || $entry->prize_id !== $prize->id) {
            abort(404);
        }

        if ($entry->status !== 'won') {
            return back()->with('error', 'Solo se pueden resetear participaciones ganadoras.');
        }

        $entry->update([
            'status' => 'pending',
            'drawn_at' => null,
            'order' => null
        ]);

        return back()->with('success', 'Participación reseteada exitosamente.');
    }

    /**
     * Delete a specific raffle entry.
     */
    public function deleteEntry(Event $event, Prize $prize, RaffleEntry $entry)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id || $entry->prize_id !== $prize->id) {
            abort(404);
        }

        // Don't allow deletion of winner entries
        if ($entry->status === 'won') {
            return back()->with('error', 'No se pueden eliminar participaciones ganadoras.');
        }

        $entry->delete();

        return back()->with('success', 'Participación eliminada exitosamente.');
    }

    /**
     * Manual winner selection for a prize.
     */
    public function selectWinner(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'send_notification' => 'boolean'
        ]);

        // Verify guest is eligible and has an entry
        $guest = Guest::findOrFail($validated['guest_id']);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        $entry = RaffleEntry::where('prize_id', $prize->id)
            ->where('guest_id', $guest->id)
            ->where('status', 'pending')
            ->first();

        if (!$entry) {
            return back()->with('error', 'El invitado seleccionado no tiene una participación válida para este premio.');
        }

        try {
            // Get the next order number
            $nextOrder = $prize->raffleEntries()->where('status', 'won')->max('order') + 1;

            $entry->update([
                'status' => 'won',
                'drawn_at' => now(),
                'order' => $nextOrder
            ]);

            // Send notification if requested
            if ($validated['send_notification'] ?? true) {
                $this->raffleService->notifyWinner($entry);
            }

            $message = "¡{$guest->full_name} seleccionado como ganador manualmente!";
            if ($validated['send_notification'] ?? true) {
                $message .= ' (se enviará notificación por email)';
            }

            return back()->with('success', $message);

        } catch (Exception $e) {
            return back()->with('error', 'Error al seleccionar ganador: ' . $e->getMessage());
        }
    }

    /**
     * Get live raffle data for real-time updates.
     */
    public function liveData(Event $event)
    {
        $this->authorize('view', $event);

        $statistics = $this->raffleService->getRaffleStatistics($event);
        
        $recentWinners = RaffleEntry::with(['guest', 'prize'])
            ->whereHas('prize', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })
            ->where('status', 'won')
            ->orderBy('drawn_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($entry) {
                return [
                    'guest_name' => $entry->guest->full_name,
                    'prize_name' => $entry->prize->name,
                    'drawn_at' => $entry->drawn_at->format('H:i:s'),
                    'order' => $entry->order
                ];
            });

        return response()->json([
            'statistics' => $statistics,
            'recent_winners' => $recentWinners
        ]);
    }
}