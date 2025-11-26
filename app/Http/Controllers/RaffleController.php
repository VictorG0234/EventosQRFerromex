<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Prize;
use App\Models\Guest;
use App\Models\RaffleEntry;
use App\Services\RaffleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use Carbon\Carbon;

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
            ->where('name', '!=', 'Rifa General') // Excluir el premio especial
            ->withCount(['raffleEntries'])
            ->get()
            ->map(function ($prize) {
                // RIFA GENERAL: usar tipo 'general'
                $eligibleCount = $this->raffleService->getEligibleGuests($prize, 'general')->count();
                
                // Contar participantes únicos (unique guest_id) que participaron en este premio
                $uniqueParticipants = DB::table('raffle_entries')
                    ->where('prize_id', $prize->id)
                    ->selectRaw('COUNT(DISTINCT guest_id) as count')
                    ->first()
                    ->count ?? 0;
                
                // Contar ganadores únicos (máximo debería ser 1 ya que stock=1)
                // Si hay múltiples ganadores, solo contar 1 (el más reciente)
                $winnersCount = $prize->raffleEntries()
                    ->where('status', 'won')
                    ->count();
                
                // El stock siempre es 1 o 0 (cada premio tiene stock=1 inicialmente)
                // Si stock=0, ya se rifó. Si stock=1, aún no se rifó.
                $originalStock = 1; // Cada premio se crea con stock=1
                $currentStock = $prize->stock; // Stock actual en BD (0 o 1)
                
                // Stock disponible: si hay ganadores o stock=0, entonces remaining_stock=0
                // Si no hay ganadores y stock=1, entonces remaining_stock=1
                $remainingStock = ($winnersCount > 0 || $currentStock == 0) ? 0 : 1;
                
                return [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'category' => $prize->category,
                    'stock' => $originalStock, // Stock original (siempre 1)
                    'current_stock' => $currentStock, // Stock actual en BD
                    'value' => $prize->value,
                    'image' => $prize->image,
                    'participants_count' => $prize->raffle_entries_count, // Total de entradas
                    'unique_participants_count' => $uniqueParticipants, // Participantes únicos
                    'winners_count' => min($winnersCount, 1), // Máximo 1 ganador (stock=1)
                    'eligible_count' => $eligibleCount,
                    'remaining_stock' => $remainingStock, // 0 o 1
                    'is_available' => $prize->isAvailable(),
                    'can_raffle' => $eligibleCount > 0 && $prize->isAvailable()
                ];
            });

        $statistics = $this->raffleService->getRaffleStatistics($event);

        // Obtener estado de la rifa pública
        $publicRaffleStatus = 'pendiente';
        $publicRaffleCompleted = 0;
        $publicRaffleTotal = $prizes->count();
        
        if ($publicRaffleTotal > 0) {
            $publicRaffleCompleted = $prizes->filter(function ($prize) {
                return $prize['winners_count'] > 0;
            })->count();
            
            if ($publicRaffleCompleted === $publicRaffleTotal) {
                $publicRaffleStatus = 'completa';
            } elseif ($publicRaffleCompleted > 0) {
                $publicRaffleStatus = 'en_progreso';
            }
        }

        // Obtener estado de la rifa general
        $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($event);
        $generalWinnersCount = RaffleEntry::where('event_id', $event->id)
            ->where('prize_id', $generalPrize->id)
            ->where('status', 'won')
            ->count();
        
        $generalRaffleStatus = $generalWinnersCount > 0 ? 'completa' : 'pendiente';

        return Inertia::render('Events/Raffle/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date
            ],
            'prizes' => $prizes,
            'statistics' => $statistics,
            'public_raffle_status' => $publicRaffleStatus,
            'public_raffle_completed' => $publicRaffleCompleted,
            'public_raffle_total' => $publicRaffleTotal,
            'general_raffle_status' => $generalRaffleStatus,
            'general_winners_count' => $generalWinnersCount,
            'total_prizes' => $statistics['total_prizes'] ?? 0
        ]);
    }

    /**
     * Display the public raffle interface.
     */
    public function drawCards(Event $event)
    {
        $this->authorize('view', $event);

        // Obtener todos los premios activos - mostrar cada premio individualmente
        $prizes = $event->prizes()
            ->where('active', true)
            ->where('name', '!=', 'Rifa General') // Excluir el premio especial
            ->withCount(['raffleEntries', 'raffleEntries as winners_count' => function ($query) {
                $query->where('status', 'won');
            }])
            ->with(['raffleEntries' => function ($query) {
                $query->where('status', 'won')
                    ->with('guest')
                    ->orderBy('drawn_at', 'asc');
            }])
            ->get()
            ->map(function ($prize) {
                // RIFA PÚBLICA: usar tipo 'public'
                $eligibleCount = $this->raffleService->getEligibleGuests($prize, 'public')->count();
                
                // Obtener ganadores ordenados por drawn_at ascendente
                $winners = $prize->raffleEntries
                    ->where('status', 'won')
                    ->sortBy('drawn_at')
                    ->values()
                    ->map(function ($entry) {
                        return [
                            'id' => $entry->guest->id,
                            'name' => $entry->guest->nombre_completo,
                            'company' => $entry->guest->compania,
                            'employee_number' => $entry->guest->numero_empleado,
                            'drawn_at' => $entry->drawn_at?->format('d/m/Y H:i:s'),
                        ];
                    });
                
                // El stock siempre es 1 según el usuario
                $originalStock = 1;
                
                return [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'category' => $prize->category,
                    'stock' => $originalStock, // Stock siempre es 1 para mostrar 1 card
                    'current_stock' => $prize->stock, // Stock actual en BD
                    'value' => $prize->value,
                    'image' => $prize->image,
                    'participants_count' => $prize->raffle_entries_count,
                    'winners_count' => $prize->winners_count,
                    'winners' => $winners->toArray(),
                    'eligible_count' => $eligibleCount,
                    'remaining_stock' => $prize->stock,
                    'is_available' => $prize->active,
                    'can_raffle' => $eligibleCount > 0 && $prize->active
                ];
            });

        return Inertia::render('Events/Draw/Cards', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prizes' => $prizes
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

        // RIFA PÚBLICA: usar tipo 'public' (las rifas desde esta interfaz son públicas)
        $eligibleGuests = $this->raffleService->getEligibleGuests($prize, 'public');
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
                'stock' => 1, // Stock original siempre es 1
                'current_stock' => $prize->stock, // Stock actual en BD (0 o 1)
                'value' => $prize->value,
                'image' => $prize->image,
                'stock_percentage' => $prize->getStockPercentage()
            ],
            'eligible_guests' => $eligibleGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->puesto,
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

            // RIFA GENERAL: usar tipo 'general' (este método se usa desde la interfaz general)
            $result = $this->raffleService->createRaffleEntries($prize, 'general');

            return back()->with('success', "Se crearon {$result['entries_created']} participaciones para la rifa del premio \"{$prize->name}\".");
            
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
            // RIFA GENERAL: usar tipo 'general'
            $result = $this->raffleService->drawRaffle($prize, $validated['quantity'], $validated['send_notification'] ?? true, 'general');

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
                            'work_area' => $entry->guest->puesto,
                            'email' => $entry->guest->email
                        ],
                        'drawn_at' => $entry->drawn_at->format('d/m/Y H:i:s'),
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
                        'work_area' => $entry->guest->puesto,
                        'email' => $entry->guest->email
                    ],
                    'status' => $entry->status,
                    'drawn_at' => $entry->drawn_at?->format('d/m/Y H:i:s'),
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
            $entry->update([
                'status' => 'won',
                'drawn_at' => now(),
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
            ->where('event_id', $event->id)
            ->where('status', 'won')
            ->whereHas('prize') // Solo entradas con premio (incluye Rifa General)
            ->orderBy('drawn_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($entry) {
                return [
                    'guest_name' => $entry->guest->full_name,
                    'prize_name' => $entry->prize->name,
                    'drawn_at' => $entry->drawn_at->format('H:i:s'),
                ];
            });

        return response()->json([
            'statistics' => $statistics,
            'recent_winners' => $recentWinners
        ]);
    }

    /**
     * Get all attendees (guests with attendance) for an event.
     */
    public function getAttendees(Event $event)
    {
        $this->authorize('view', $event);

        $attendees = $event->guests()
            ->whereHas('attendance')
            ->get()
            ->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->nombre_completo,
                    'employee_number' => $guest->numero_empleado,
                    'email' => $guest->correo,
                    'compania' => $guest->compania,	
                ];
            });

        return response()->json([
            'attendees' => $attendees,
            'total' => $attendees->count()
        ]);
    }

    /**
     * Execute a single raffle draw and return the winner.
     */
    public function drawSingle(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        try {
            $validated = $request->validate([
                'card_index' => 'nullable|integer|min:0',
                'reset_previous' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Resetear ganador anterior si es necesario
            if ($validated['reset_previous'] ?? false) {
                $this->resetPreviousWinner($prize, $validated['card_index'] ?? 0);
            }

            // Asegurar que hay participaciones pendientes
            $pendingEntries = $this->ensurePendingEntries($prize);
            if ($pendingEntries === false) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron crear participaciones pendientes para este premio.'
                ], 422);
            }

            // Realizar la rifa
            $prize->refresh();
            $result = $this->raffleService->drawRaffle($prize, 1, false, 'public');

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Error al realizar la rifa',
                    'details' => $result
                ], 422);
            }

            DB::commit();

            $prize->refresh();
            $winner = $result['winners'][0];

            return response()->json([
                'success' => true,
                'winner' => [
                    'id' => $winner->guest->id,
                    'name' => $winner->guest->nombre_completo,
                    'employee_number' => $winner->guest->numero_empleado,
                    'email' => $winner->guest->correo,
                    'compania' => $winner->guest->compania,
                ],
                'remaining_stock' => $prize->stock,
                'winners_count' => $prize->raffleEntries()->where('status', 'won')->count()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            
            // Log del error completo para debugging
            \Log::error('Error en drawSingle', [
                'event_id' => $event->id,
                'prize_id' => $prize->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la rifa: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 422);
        }
    }

    /**
     * Display the general raffle interface.
     */
    public function drawGeneral(Event $event)
    {
        $this->authorize('view', $event);

        // Obtener el premio especial "Rifa General"
        $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($event);

        // Obtener ganadores de la rifa general (usando el premio especial)
        $winners = RaffleEntry::where('event_id', $event->id)
            ->where('prize_id', $generalPrize->id)
            ->where('status', 'won')
            ->with('guest')
            ->orderBy('drawn_at', 'asc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->guest->id,
                    'name' => $entry->guest->nombre_completo,
                    'company' => $entry->guest->compania,
                    'employee_number' => $entry->guest->numero_empleado,
                    'drawn_at' => $entry->drawn_at?->format('d/m/Y H:i:s'),
                ];
            });

        // Obtener invitados elegibles
        $eligibleGuests = $this->raffleService->getEligibleGuestsForGeneralRaffle($event);
        
        // Verificar si ya hay ganadores
        $hasWinners = $winners->count() > 0;
        
        // Verificar si se puede rifar (hay invitados elegibles y no hay ganadores o se permite volver a rifar)
        $canRaffle = $eligibleGuests->count() > 0;

        return Inertia::render('Events/Draw/General', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'winners' => $winners->toArray(),
            'winners_count' => $winners->count(),
            'eligible_count' => $eligibleGuests->count(),
            'can_raffle' => $canRaffle,
            'has_winners' => $hasWinners
        ]);
    }

    /**
     * Execute the general raffle draw (15 winners).
     */
    public function executeGeneralDraw(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'winners_count' => 'nullable|integer|min:1|max:100',
            'send_notification' => 'boolean',
            'reset_previous' => 'boolean'
        ]);

        try {
            $winnersCount = $validated['winners_count'] ?? 76;
            $sendNotification = $validated['send_notification'] ?? false;
            $resetPrevious = $validated['reset_previous'] ?? false;

            // Obtener el premio especial "Rifa General"
            $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($event);

            // Si se indica resetear ganadores anteriores
            if ($resetPrevious) {
                // Resetear todos los ganadores anteriores de la rifa general
                RaffleEntry::where('event_id', $event->id)
                    ->where('prize_id', $generalPrize->id)
                    ->where('status', 'won')
                    ->update([
                        'status' => 'pending',
                        'drawn_at' => null
                    ]);
            }

            // Realizar el sorteo
            $result = $this->raffleService->drawGeneralRaffle($event, $winnersCount, $sendNotification);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 422);
            }

            $winnerNames = $result['winners']->map(function ($entry) {
                return $entry->guest->nombre_completo;
            })->implode(', ');

            $message = "¡Rifa general realizada exitosamente! Se seleccionaron {$result['winners_count']} ganadores.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'winners' => $result['winners']->map(function ($entry) {
                    return [
                        'id' => $entry->guest->id,
                        'name' => $entry->guest->nombre_completo,
                        'company' => $entry->guest->compania,
                        'employee_number' => $entry->guest->numero_empleado,
                        'email' => $entry->guest->correo,
                        'drawn_at' => $entry->drawn_at->format('d/m/Y H:i:s'),
                    ];
                }),
                'winners_count' => $result['winners_count']
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            \Log::error('Error en executeGeneralDraw', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la rifa general: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Re-seleccionar un ganador específico de la rifa general.
     * Mantiene los otros 14 ganadores intactos.
     */
    public function reselectGeneralWinner(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id'
        ]);

        try {
            // Obtener el premio especial "Rifa General"
            $generalPrize = $this->raffleService->getOrCreateGeneralRafflePrize($event);

            // Verificar que el guest pertenece al evento
            $guest = Guest::findOrFail($validated['guest_id']);
            if ($guest->event_id !== $event->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El invitado no pertenece a este evento.'
                ], 422);
            }

            // Obtener la entrada ganadora actual del guest
            $currentWinnerEntry = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('guest_id', $guest->id)
                ->where('status', 'won')
                ->first();

            if (!$currentWinnerEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'El invitado seleccionado no es un ganador actual de la rifa general.'
                ], 422);
            }

            // Obtener los IDs de los otros ganadores (para excluirlos del nuevo sorteo)
            $otherWinnersIds = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'won')
                ->where('guest_id', '!=', $guest->id)
                ->pluck('guest_id')
                ->toArray();

            // Resetear el ganador actual (marcar como pending)
            $currentWinnerEntry->update([
                'status' => 'pending',
                'drawn_at' => null
            ]);
            $currentWinnerEntry->refresh();

            // Obtener invitados elegibles para la rifa general (excluyendo los otros 14 ganadores)
            $eligibleGuests = $this->raffleService->getEligibleGuestsForGeneralRaffle($event)
                ->whereNotIn('id', $otherWinnersIds);

            // Obtener entradas pendientes de los elegibles (excluyendo los otros ganadores)
            // Incluir también la entrada del guest que se está reemplazando (ya está como pending)
            $pendingEntries = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'pending')
                ->whereNotIn('guest_id', $otherWinnersIds)
                ->with('guest')
                ->get();

            // Si la entrada del guest actual no está en las pendientes (por alguna razón), agregarla
            if (!$pendingEntries->contains('id', $currentWinnerEntry->id)) {
                $currentWinnerEntry->load('guest');
                $pendingEntries->push($currentWinnerEntry);
            }

            // Contar cuántos ganadores IMEX hay actualmente (excluyendo el que se está reemplazando)
            $currentInexWinnersCount = RaffleEntry::where('event_id', $event->id)
                ->where('prize_id', $generalPrize->id)
                ->where('status', 'won')
                ->where('guest_id', '!=', $guest->id)
                ->whereHas('guest', function ($q) {
                    $q->where('compania', 'IMEX');
                })
                ->count();

            // Verificar si el ganador actual es IMEX
            $currentWinnerIsInex = strtoupper(trim($guest->compania ?? '')) === 'IMEX';

            // Filtrar para asegurar que solo incluimos elegibles
            // REGLA 1: Debe haber solo 2 ganadores IMEX
            $eligibleEntries = $pendingEntries->filter(function ($entry) use ($eligibleGuests, $guest, $currentInexWinnersCount, $currentWinnerIsInex) {
                // Excluir al guest actual de la selección (no puede re-seleccionarse a sí mismo)
                if ($entry->guest_id === $guest->id) {
                    return false;
                }

                // Verificar que el guest es elegible
                if (!$eligibleGuests->contains('id', $entry->guest_id)) {
                    return false;
                }

                // REGLA 1: Si ya hay 2 ganadores IMEX y el ganador actual NO es IMEX,
                // entonces el nuevo ganador NO puede ser IMEX
                if ($currentInexWinnersCount >= 2 && !$currentWinnerIsInex) {
                    $entryIsInex = strtoupper(trim($entry->guest->compania ?? '')) === 'IMEX';
                    if ($entryIsInex) {
                        return false; // No permitir más ganadores IMEX
                    }
                }

                // REGLA 1: Si ya hay 1 ganador IMEX y el ganador actual es IMEX,
                // entonces el nuevo ganador puede ser IMEX (para mantener 2)
                // Si ya hay 2 ganadores IMEX y el ganador actual es IMEX,
                // entonces el nuevo ganador NO puede ser IMEX (para mantener 2)
                if ($currentInexWinnersCount >= 2 && $currentWinnerIsInex) {
                    $entryIsInex = strtoupper(trim($entry->guest->compania ?? '')) === 'IMEX';
                    if ($entryIsInex) {
                        return false; // No permitir más ganadores IMEX
                    }
                }

                return true;
            });

            if ($eligibleEntries->isEmpty()) {
                // Si no hay elegibles (además del actual), restaurar el ganador original
                $currentWinnerEntry->update([
                    'status' => 'won',
                    'drawn_at' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No hay otros participantes elegibles disponibles para reemplazar este ganador. Se mantiene el ganador original.'
                ], 422);
            }

            // Seleccionar un nuevo ganador aleatoriamente (excluyendo al ganador actual)
            $newWinnerEntry = $eligibleEntries->random();

            // Marcar el nuevo ganador
            $newWinnerEntry->update([
                'status' => 'won',
                'drawn_at' => Carbon::now(),
                'raffle_metadata' => array_merge($newWinnerEntry->raffle_metadata ?? [], [
                    'raffle_type' => 'general',
                    'drawn_at' => now(),
                    'reselect' => true,
                    'replaced_guest_id' => $guest->id
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ganador re-seleccionado exitosamente.',
                'winner' => [
                    'id' => $newWinnerEntry->guest->id,
                    'name' => $newWinnerEntry->guest->nombre_completo,
                    'company' => $newWinnerEntry->guest->compania,
                    'employee_number' => $newWinnerEntry->guest->numero_empleado,
                    'email' => $newWinnerEntry->guest->correo,
                    'drawn_at' => $newWinnerEntry->drawn_at->format('d/m/Y H:i:s'),
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            \Log::error('Error en reselectGeneralWinner', [
                'event_id' => $event->id,
                'guest_id' => $validated['guest_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al re-seleccionar ganador: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Resetear un ganador anterior específico
     */
    private function resetPreviousWinner(Prize $prize, int $cardIndex): void
    {
        $winners = RaffleEntry::where('prize_id', $prize->id)
            ->where('status', 'won')
            ->orderBy('drawn_at', 'asc')
            ->get();

        if ($cardIndex < $winners->count()) {
            $previousWinner = $winners[$cardIndex];
            $previousWinner->update([
                'status' => 'pending',
                'drawn_at' => null,
            ]);
            
            $prize->increment('stock', 1);
            
            // Resetear todas las entradas "lost" para permitir nueva participación
            RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'lost')
                ->update([
                    'status' => 'pending',
                    'drawn_at' => null
                ]);
        }
        
        $prize->refresh();
    }

    /**
     * Asegurar que hay participaciones pendientes, creándolas si es necesario
     */
    private function ensurePendingEntries(Prize $prize): bool
    {
        $pendingEntries = RaffleEntry::where('prize_id', $prize->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingEntries > 0) {
            return true;
        }

        // Intentar resetear entradas "lost"
        $lostEntries = RaffleEntry::where('prize_id', $prize->id)
            ->where('status', 'lost')
            ->count();
        
        if ($lostEntries > 0) {
            RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'lost')
                ->update([
                    'status' => 'pending',
                    'drawn_at' => null
                ]);
            
            $prize->refresh();
            if ($prize->stock <= 0) {
                $prize->increment('stock', 1);
                $prize->refresh();
            }
            
            $pendingEntries = RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->count();
        }
        
        // Si aún no hay pendientes, crear nuevas participaciones
        if ($pendingEntries === 0) {
            $createResult = $this->raffleService->createRaffleEntries($prize, 'public');
            
            if (!$createResult['success']) {
                return false;
            }
            
            $pendingEntries = RaffleEntry::where('prize_id', $prize->id)
                ->where('status', 'pending')
                ->count();
            
            if ($pendingEntries === 0) {
                return false;
            }
        }
        
        return true;
    }

}