<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Attendance;
use App\Services\QrCodeService;
use App\Services\EmailService;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $qrService;
    protected $emailService;

    public function __construct(QrCodeService $qrService, EmailService $emailService)
    {
        $this->qrService = $qrService;
        $this->emailService = $emailService;
    }

    /**
     * Show QR scanner interface for event.
     */
    public function scanner(Event $event)
    {
        $this->authorize('view', $event);

        $statistics = [
            'total_guests' => $event->guests()->count(),
            'total_attendances' => $event->attendances()->count(),
            'attendance_rate' => $event->getAttendanceRate(),
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
                        'work_area' => $attendance->guest->area_laboral,
                        'attended_at' => $attendance->created_at->format('H:i:s'),
                        'time_ago' => $attendance->created_at->diffForHumans(),
                    ];
                })
        ];

        return Inertia::render('Events/Attendance/Scanner', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->format('d/m/Y H:i'),
                'location' => $event->location,
                'is_active' => $event->is_active,
            ],
            'statistics' => $statistics
        ]);
    }

    /**
     * Process QR code scan and register attendance.
     */
    public function scan(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'qr_data' => 'required|string'
        ]);

        // Validar el código QR
        $validation = $this->qrService->validateQrCode($request->qr_data, $event->id);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
                'type' => 'error'
            ], 422);
        }

        $guest = $validation['guest'];

        // Verificar si ya está registrado
        $existingAttendance = Attendance::where('event_id', $event->id)
            ->where('guest_id', $guest->id)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => "El invitado {$guest->full_name} ya registró su asistencia el " . 
                           $existingAttendance->created_at->format('d/m/Y H:i:s'),
                'type' => 'warning',
                'guest' => [
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->area_laboral,
                    'attended_at' => $existingAttendance->created_at->format('d/m/Y H:i:s')
                ]
            ]);
        }

        // Registrar asistencia
        try {
            DB::beginTransaction();

            $attendance = Attendance::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id
            ]);

            DB::commit();

            // Enviar email de confirmación de asistencia si el invitado tiene email
            if (!empty($guest->email)) {
                SendEmailJob::dispatch('attendance_confirmation', $guest);
            }

            // Estadísticas actualizadas
            $newStatistics = [
                'total_attendances' => $event->attendances()->count(),
                'attendance_rate' => $event->getAttendanceRate()
            ];

            return response()->json([
                'success' => true,
                'message' => "¡Bienvenido {$guest->full_name}! Asistencia registrada exitosamente.",
                'type' => 'success',
                'guest' => [
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->area_laboral,
                    'attended_at' => $attendance->created_at->format('d/m/Y H:i:s'),
                    'raffle_categories' => $guest->premios_rifa
                ],
                'statistics' => $newStatistics
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia. Intente nuevamente.',
                'type' => 'error'
            ], 500);
        }
    }

    /**
     * Manual attendance registration for troubleshooting.
     */
    public function manualRegister(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'employee_number' => 'required|string',
            'reason' => 'nullable|string|max:255'
        ]);

        // Buscar el invitado
        $guest = Guest::where('event_id', $event->id)
            ->where('numero_empleado', $request->employee_number)
            ->first();

        if (!$guest) {
            return back()->with('error', 'No se encontró un invitado con ese número de empleado.');
        }

        // Verificar si ya está registrado
        $existingAttendance = Attendance::where('event_id', $event->id)
            ->where('guest_id', $guest->id)
            ->first();

        if ($existingAttendance) {
            return back()->with('error', 
                "El invitado {$guest->full_name} ya registró su asistencia el " . 
                $existingAttendance->created_at->format('d/m/Y H:i:s')
            );
        }

        // Registrar asistencia manual
        try {
            Attendance::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'notes' => 'Registro manual: ' . ($request->reason ?? 'Sin especificar')
            ]);

            // Enviar email de confirmación de asistencia si el invitado tiene email
            if (!empty($guest->email)) {
                SendEmailJob::dispatch('attendance_confirmation', $guest);
            }

            return back()->with('success', 
                "Asistencia registrada manualmente para {$guest->full_name}."
            );

        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar la asistencia manual.');
        }
    }

    /**
     * Show attendance list for event.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $attendances = $event->attendances()
            ->with('guest')
            ->latest()
            ->paginate(50)
            ->through(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'guest' => [
                        'id' => $attendance->guest->id,
                        'name' => $attendance->guest->full_name,
                        'employee_number' => $attendance->guest->numero_empleado,
                        'work_area' => $attendance->guest->area_laboral,
                    ],
                    'attended_at' => $attendance->created_at->format('d/m/Y H:i:s'),
                    'time_ago' => $attendance->created_at->diffForHumans(),
                    'notes' => $attendance->notes,
                ];
            });

        $statistics = [
            'total_guests' => $event->guests()->count(),
            'total_attendances' => $event->attendances()->count(),
            'attendance_rate' => $event->getAttendanceRate(),
            'attendances_by_hour' => $event->attendances()
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour'),
            'attendances_by_area' => $event->attendances()
                ->join('guests', 'attendances.guest_id', '=', 'guests.id')
                ->selectRaw('guests.area_laboral, COUNT(*) as count')
                ->groupBy('guests.area_laboral')
                ->orderByDesc('count')
                ->pluck('count', 'area_laboral')
        ];

        return Inertia::render('Events/Attendance/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->format('d/m/Y H:i'),
                'location' => $event->location,
            ],
            'attendances' => $attendances,
            'statistics' => $statistics
        ]);
    }

    /**
     * Remove attendance record (for corrections).
     */
    public function destroy(Event $event, Attendance $attendance)
    {
        $this->authorize('view', $event);

        if ($attendance->event_id !== $event->id) {
            abort(404);
        }

        $guestName = $attendance->guest->full_name;
        $attendance->delete();

        return back()->with('success', "Registro de asistencia de {$guestName} eliminado.");
    }

    /**
     * Get real-time attendance statistics.
     */
    public function liveStats(Event $event)
    {
        $this->authorize('view', $event);

        return response()->json([
            'total_guests' => $event->guests()->count(),
            'total_attendances' => $event->attendances()->count(),
            'attendance_rate' => $event->getAttendanceRate(),
            'recent_attendances' => $event->attendances()
                ->with('guest')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($attendance) {
                    return [
                        'guest_name' => $attendance->guest->full_name,
                        'employee_number' => $attendance->guest->numero_empleado,
                        'attended_at' => $attendance->created_at->format('H:i:s'),
                    ];
                }),
            'timestamp' => now()->timestamp
        ]);
    }

    /**
     * Export attendance report.
     */
    public function export(Event $event)
    {
        $this->authorize('view', $event);
        
        // TODO: Implementar exportación a Excel/PDF
        return back()->with('info', 'Funcionalidad de exportación en desarrollo.');
    }
}