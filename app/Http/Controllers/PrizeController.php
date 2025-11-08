<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Prize;
use App\Services\RaffleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PrizeController extends Controller
{
    protected $raffleService;

    public function __construct(RaffleService $raffleService)
    {
        $this->raffleService = $raffleService;
    }

    /**
     * Display a listing of prizes for an event.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $prizes = $event->prizes()
            ->withCount(['raffleEntries', 'raffleEntries as winners_count' => function ($query) {
                $query->where('status', 'won');
            }])
            ->latest()
            ->get()
            ->map(function ($prize) {
                return [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'category' => $prize->category,
                    'stock' => $prize->stock,
                    'initial_stock' => $prize->initial_stock,
                    'value' => $prize->value,
                    'active' => $prize->active,
                    'image' => $prize->image,
                    'stock_percentage' => $prize->getStockPercentage(),
                    'participants_count' => $prize->raffle_entries_count,
                    'winners_count' => $prize->winners_count,
                    'is_available' => $prize->isAvailable(),
                    'created_at' => $prize->created_at->format('d/m/Y H:i')
                ];
            });

        $statistics = $this->raffleService->getRaffleStatistics($event);

        return Inertia::render('Events/Prizes/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prizes' => $prizes,
            'statistics' => $statistics
        ]);
    }

    /**
     * Show the form for creating a new prize.
     */
    public function create(Event $event)
    {
        $this->authorize('update', $event);

        // Get unique categories from guests' premios_rifa
        $categories = $event->guests()
            ->whereNotNull('premios_rifa')
            ->pluck('premios_rifa')
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        return Inertia::render('Events/Prizes/Create', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created prize.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:255',
            'stock' => 'required|integer|min:1|max:1000',
            'value' => 'nullable|numeric|min:0|max:999999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean'
        ]);

        $validated['event_id'] = $event->id;
        $validated['initial_stock'] = $validated['stock'];
        $validated['active'] = $request->boolean('active', true);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('prizes', 'public');
            $validated['image'] = $imagePath;
        }

        $prize = Prize::create($validated);

        return redirect()->route('events.prizes.index', $event)
            ->with('success', 'Premio creado exitosamente.');
    }

    /**
     * Display the specified prize.
     */
    public function show(Event $event, Prize $prize)
    {
        $this->authorize('view', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $results = $this->raffleService->getPrizeResults($prize);
        $eligibleGuests = $this->raffleService->getEligibleGuests($prize);
        $validation = $this->raffleService->validatePrize($prize);

        return Inertia::render('Events/Prizes/Show', [
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
                'initial_stock' => $prize->initial_stock,
                'value' => $prize->value,
                'active' => $prize->active,
                'image' => $prize->image,
                'stock_percentage' => $prize->getStockPercentage(),
                'created_at' => $prize->created_at->format('d/m/Y H:i')
            ],
            'results' => $results,
            'eligible_guests' => $eligibleGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->area_laboral,
                    'attended_at' => $guest->attendance?->created_at->format('d/m/Y H:i')
                ];
            }),
            'validation' => $validation
        ]);
    }

    /**
     * Show the form for editing the specified prize.
     */
    public function edit(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        // Check if prize has raffle entries
        $hasEntries = $prize->raffleEntries()->exists();

        // Get categories
        $categories = $event->guests()
            ->whereNotNull('premios_rifa')
            ->pluck('premios_rifa')
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        return Inertia::render('Events/Prizes/Edit', [
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
                'initial_stock' => $prize->initial_stock,
                'value' => $prize->value,
                'active' => $prize->active,
                'image' => $prize->image
            ],
            'categories' => $categories,
            'has_entries' => $hasEntries
        ]);
    }

    /**
     * Update the specified prize.
     */
    public function update(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $hasEntries = $prize->raffleEntries()->exists();

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'value' => 'nullable|numeric|min:0|max:999999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean'
        ];

        // Only allow category and stock changes if no entries exist
        if (!$hasEntries) {
            $rules['category'] = 'required|string|max:255';
            $rules['stock'] = 'required|integer|min:1|max:1000';
        }

        $validated = $request->validate($rules);
        $validated['active'] = $request->boolean('active');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($prize->image) {
                Storage::disk('public')->delete($prize->image);
            }
            $imagePath = $request->file('image')->store('prizes', 'public');
            $validated['image'] = $imagePath;
        }

        // Update initial_stock if stock changed and no entries exist
        if (!$hasEntries && isset($validated['stock'])) {
            $validated['initial_stock'] = $validated['stock'];
        }

        $prize->update($validated);

        return redirect()->route('events.prizes.index', $event)
            ->with('success', 'Premio actualizado exitosamente.');
    }

    /**
     * Remove the specified prize.
     */
    public function destroy(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        // Check if prize has raffle entries
        if ($prize->raffleEntries()->exists()) {
            return back()->with('error', 'No se puede eliminar un premio que ya tiene participaciones en rifas.');
        }

        // Delete image if exists
        if ($prize->image) {
            Storage::disk('public')->delete($prize->image);
        }

        $prize->delete();

        return redirect()->route('events.prizes.index', $event)
            ->with('success', 'Premio eliminado exitosamente.');
    }

    /**
     * Toggle prize active status.
     */
    public function toggleActive(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $prize->update(['active' => !$prize->active]);

        $status = $prize->active ? 'activado' : 'desactivado';

        return back()->with('success', "Premio {$status} exitosamente.");
    }
}