<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    /**
     * Display a listing of events for the authenticated user.
     */
    public function index()
    {
        $events = Auth::user()->events()
            ->withCount(['guests', 'prizes', 'attendances'])
            ->latest()
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'event_date' => $event->event_date->format('d/m/Y'),
                    'start_time' => $event->start_time ? $event->start_time : null,
                    'location' => $event->location,
                    'status' => $event->status,
                    'is_active' => $event->status === 'active',
                    'guests_count' => $event->guests_count,
                    'prizes_count' => $event->prizes_count,
                    'attendances_count' => $event->attendances_count,
                    'attendance_rate' => $event->guests_count > 0 ? 
                        round(($event->attendances_count / $event->guests_count) * 100, 2) : 0,
                    'created_at' => $event->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Events/Index', [
            'events' => $events
        ]);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
        ]);

        // Preparar datos para la base de datos
        $eventData = [
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'event_date' => $validated['date'],
            'start_time' => $validated['time'],
            'location' => $validated['location'],
            'status' => 'active',
        ];

        $event = Event::create($eventData);

        return redirect()->route('events.show', $event)
            ->with('success', 'Evento creado exitosamente.');
    }

    /**
     * Display the specified event with statistics.
     */
    public function show(Event $event)
    {
        // Verificar que el evento pertenece al usuario autenticado
        $this->authorize('view', $event);

        $event->load(['guests', 'prizes', 'attendances.guest']);
        
        // Estadísticas del evento
        $statistics = [
            'total_guests' => $event->guests->count(),
            'total_attendances' => $event->attendances->count(),
            'attendance_rate' => $event->guests->count() > 0 ? 
                round(($event->attendances->count() / $event->guests->count()) * 100, 2) : 0,
            'total_prizes' => $event->prizes->count(),
            'total_prize_stock' => $event->prizes->sum('stock'),
            'prizes_by_category' => $event->prizes->groupBy('category')->map->sum('stock'),
            'attendances_by_hour' => $event->attendances
                ->groupBy(fn($attendance) => $attendance->created_at->format('H:00'))
                ->map->count()
                ->sortKeys(),
        ];

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'event_date' => $event->event_date->format('d/m/Y'),
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location' => $event->location,
                'status' => $event->status,
                'is_active' => $event->status === 'active',
                'created_at' => $event->created_at->format('d/m/Y H:i'),
                'public_token' => $event->public_token,
                'public_url' => route('public.event.register', $event->public_token),
            ],
            'statistics' => $statistics,
            'recent_attendances' => $event->attendances()
                ->with('guest')
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'guest_name' => $attendance->guest->full_name,
                        'guest_employee_number' => $attendance->guest->employee_number,
                        'attended_at' => $attendance->created_at->format('d/m/Y H:i:s'),
                    ];
                })
        ]);
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'date' => $event->event_date->format('Y-m-d'),
                'time' => $event->start_time ? (is_string($event->start_time) ? $event->start_time : $event->start_time->format('H:i')) : '09:00',
                'location' => $event->location,
                'status' => $event->status,
                'is_active' => $event->status === 'active',
                'created_at' => $event->created_at->format('d/m/Y H:i'),
            ]
        ]);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
        ]);

        // Preparar datos para actualización
        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'event_date' => $validated['date'],
            'start_time' => $validated['time'],
            'location' => $validated['location'],
        ];

        $event->update($updateData);

        return redirect()->route('events.show', $event)
            ->with('success', 'Evento actualizado exitosamente.');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $eventName = $event->name;
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', "Evento '{$eventName}' eliminado exitosamente.");
    }

    /**
     * Toggle event active status.
     */
    public function toggleActive(Event $event)
    {
        $this->authorize('update', $event);

        $newStatus = $event->status === 'active' ? 'cancelled' : 'active';
        $event->update(['status' => $newStatus]);

        $statusText = $newStatus === 'active' ? 'activado' : 'desactivado';

        return back()->with('success', "Evento {$statusText} exitosamente.");
    }

    /**
     * Get event statistics for dashboard.
     */
    public function statistics(Event $event)
    {
        $this->authorize('view', $event);

        $stats = [
            'overview' => [
                'total_guests' => $event->guests()->count(),
                'total_attendances' => $event->attendances()->count(),
                'attendance_rate' => $event->getAttendanceRate(),
                'total_prizes' => $event->prizes()->count(),
                'active_raffle_entries' => $event->raffleEntries()->count(),
            ],
            'hourly_attendance' => $event->attendances()
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour'),
            'attendance_by_area' => $event->attendances()
                ->join('guests', 'attendances.guest_id', '=', 'guests.id')
                ->selectRaw('guests.work_area, COUNT(*) as count')
                ->groupBy('guests.work_area')
                ->get()
                ->pluck('count', 'work_area'),
            'prize_categories' => $event->prizes()
                ->selectRaw('category, COUNT(*) as total_prizes, SUM(stock) as total_stock')
                ->groupBy('category')
                ->get(),
        ];

        return response()->json($stats);
    }
}