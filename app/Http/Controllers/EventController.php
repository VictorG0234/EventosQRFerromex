<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Helpers\StatisticsHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

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
        
        $statistics = StatisticsHelper::getCompleteEventStatistics($event);

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
                        'employee_number' => $attendance->guest->numero_empleado,
                        'attended_at' => \Carbon\Carbon::parse($attendance->scanned_at ?? $attendance->created_at)
                            ->setTimezone('America/Mexico_City')
                            ->format('d/m/Y H:i:s'),
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
     * Get event statistics for dashboard (AJAX endpoint).
     */
    public function statistics(Event $event)
    {
        $this->authorize('view', $event);

        $stats = StatisticsHelper::getCompleteEventStatistics($event);
        
        // Agregar asistencias recientes
        $stats['recent_attendances'] = $event->attendances()
            ->with('guest')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'guest_name' => $attendance->guest->full_name,
                    'employee_number' => $attendance->guest->numero_empleado,
                    'attended_at' => ($attendance->scanned_at ?? $attendance->created_at)->format('d/m/Y H:i:s'),
                ];
            });

        return response()->json($stats);
    }

    /**
     * Show statistics report page
     */
    public function statisticsReport(Event $event)
    {
        $this->authorize('view', $event);

        $event->load(['guests', 'prizes', 'attendances.guest']);
        
        $statistics = StatisticsHelper::getCompleteEventStatisticsForReport($event);
        
        // Obtener todas las asistencias
        $attendances = StatisticsHelper::formatAttendancesForDisplay(
            $event->attendances()
                ->with('guest')
                ->orderBy('scanned_at', 'desc')
                ->get()
        );

        return Inertia::render('Events/StatisticsReport', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'event_date' => $event->event_date->format('d/m/Y'),
                'start_time' => $event->start_time,
                'location' => $event->location,
                'status' => $event->status,
            ],
            'statistics' => $statistics,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Generate PDF report
     */
    public function generatePDF(Event $event)
    {
        try {
            $this->authorize('view', $event);

            $event->load(['guests', 'prizes', 'attendances.guest']);
            
            $statistics = StatisticsHelper::getCompleteEventStatisticsForReport($event);

            // Obtener todas las asistencias formateadas para PDF
            $attendances = $event->attendances
                ->sortByDesc('created_at')
                ->map(function ($attendance) {
                    return [
                        'guest_name' => $attendance->guest->full_name ?? 'N/A',
                        'employee_number' => $attendance->guest->numero_empleado ?? 'N/A',
                        'work_area' => $attendance->guest->puesto ?? 'N/A',
                        'attended_at' => \Carbon\Carbon::parse($attendance->scanned_at ?? $attendance->created_at)
                            ->setTimezone('America/Mexico_City')
                            ->format('d/m/Y H:i:s'),
                    ];
                })
                ->values()
                ->toArray();

            $pdf = Pdf::loadView('pdf.statistics-report', [
                'event' => $event,
                'statistics' => $statistics,
                'attendances' => $attendances,
            ]);

            return $pdf->download('estadisticas-' . Str::slug($event->name) . '.pdf');
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error al generar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar logs de invitaciones enviadas en tiempo real
     */
    public function realtimeLogs(Event $event)
    {
        $this->authorize('view', $event);

        // Leer el archivo de log de Laravel
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            
            // Buscar líneas que contengan "Email de invitación enviado" y el event_id
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Email de invitación enviado ({.*?"event_id":' . $event->id . '.*?})/i';
            
            if (preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $timestamp = $match[1];
                    $jsonData = $match[2];
                    
                    // Decodificar el JSON
                    $data = json_decode($jsonData, true);
                    
                    if ($data && isset($data['guest_id'])) {
                        $logs[] = [
                            'timestamp' => $timestamp,
                            'guest_id' => $data['guest_id'],
                            'guest_name' => $data['guest_name'] ?? 'N/A',
                            'email' => $data['email'] ?? 'N/A',
                        ];
                    }
                }
            }
        }

        // Ordenar por timestamp descendente (más recientes primero)
        usort($logs, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return Inertia::render('Events/RealtimeLogs', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->event_date->format('d/m/Y'),
                'location' => $event->location,
            ],
            'logs' => $logs,
            'total_logs' => count($logs),
        ]);
    }
}

