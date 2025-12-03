<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Services\QrCodeService;
use App\Services\EmailService;
use App\Services\RaffleService;
use App\Helpers\RaffleEntryHelper;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $qrService;
    protected $emailService;
    protected $raffleService;

    public function __construct(QrCodeService $qrService, EmailService $emailService, RaffleService $raffleService)
    {
        $this->qrService = $qrService;
        $this->emailService = $emailService;
        $this->raffleService = $raffleService;
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
                        'work_area' => $attendance->guest->puesto,
                        'attended_at' => $attendance->created_at->format('H:i:s'),
                        'time_ago' => $attendance->created_at->diffForHumans(),
                        'scan_count' => $attendance->scan_count,
                        'exceeded_limit' => $attendance->hasExceededLimit(),
                    ];
                })
        ];

        return Inertia::render('Events/Attendance/Scanner', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->event_date->format('d/m/Y') . ($event->start_time ? ' ' . $event->start_time->format('H:i') : ''),
                'location' => $event->location,
                'is_active' => $event->status === 'active',
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
            // Verificar si ha alcanzado el límite de escaneos
            if ($existingAttendance->scan_count >= Attendance::MAX_SCAN_COUNT) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ LÍMITE EXCEDIDO: {$guest->full_name} ya utilizó sus {$existingAttendance->scan_count} escaneos permitidos. Primera asistencia: " . 
                               $existingAttendance->created_at->format('d/m/Y H:i:s'),
                    'type' => 'error',
                    'exceeded_limit' => true,
                    'guest' => [
                        'name' => $guest->full_name,
                        'employee_number' => $guest->numero_empleado,
                        'work_area' => $guest->puesto,
                        'attended_at' => $existingAttendance->created_at->format('d/m/Y H:i:s'),
                        'scan_count' => $existingAttendance->scan_count,
                        'last_scanned_at' => $existingAttendance->last_scanned_at?->format('d/m/Y H:i:s')
                    ]
                ], 422);
            }

            // Permitir escaneo adicional (acompañante)
            try {
                DB::beginTransaction();

                $existingAttendance->incrementScanCount();

                DB::commit();

                // Registrar en auditoría
                AuditLog::log(
                    action: 'scan',
                    model: 'Attendance',
                    modelId: $existingAttendance->id,
                    description: "Escaneo adicional #{$existingAttendance->scan_count}: {$guest->full_name} ({$guest->numero_empleado}) - Acompañante"
                );

                return response()->json([
                    'success' => true,
                    'message' => "✓ Acompañante registrado para {$guest->full_name}. Escaneo #{$existingAttendance->scan_count} de " . Attendance::MAX_SCAN_COUNT,
                    'type' => 'success',
                    'is_additional_scan' => true,
                    'guest' => [
                        'name' => $guest->full_name,
                        'employee_number' => $guest->numero_empleado,
                        'work_area' => $guest->puesto,
                        'attended_at' => $existingAttendance->created_at->format('d/m/Y H:i:s'),
                        'scan_count' => $existingAttendance->scan_count,
                        'last_scanned_at' => now()->format('d/m/Y H:i:s')
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Error al incrementar scan_count: ' . $e->getMessage(), [
                    'exception' => $e,
                    'attendance_id' => $existingAttendance->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el escaneo adicional.',
                    'type' => 'error'
                ], 500);
            }
        }

        // Registrar asistencia (primera vez)
        try {
            DB::beginTransaction();

            $attendance = Attendance::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'scanned_at' => now(),
                'scanned_by' => auth()->user()->name ?? 'Scanner QR',
                'scan_count' => 1,
                'last_scanned_at' => now(),
                'scan_metadata' => [
                    'method' => 'qr_scan',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            DB::commit();

            // Registrar en auditoría
            AuditLog::log(
                action: 'scan',
                model: 'Attendance',
                modelId: $attendance->id,
                description: "Escaneo QR exitoso: {$guest->full_name} ({$guest->numero_empleado}) en evento {$event->name}"
            );

            // Enviar email de confirmación de asistencia si el invitado tiene email
            // Usar try-catch separado para que el email no rompa el registro
            if (!empty($guest->email)) {
                try {
                    SendEmailJob::dispatch('attendance_confirmation', $guest, $event);
                } catch (\Exception $emailError) {
                    // No fallar el registro si el email falla
                }
            }

            // Crear participaciones automáticamente para todos los premios activos del evento
            RaffleEntryHelper::createAutoEntriesForGuest($guest, $event, $this->raffleService);

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
                    'work_area' => $guest->puesto,
                    'attended_at' => $attendance->created_at->format('d/m/Y H:i:s'),
                    'raffle_categories' => $guest->categoria_rifa
                ],
                'statistics' => $newStatistics
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al registrar asistencia: ' . $e->getMessage(), [
                'exception' => $e,
                'event_id' => $event->id,
                'guest_id' => $guest->id ?? null,
                'qr_data' => $request->qr_data
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia. Intente nuevamente.',
                'type' => 'error',
                'debug' => config('app.debug') ? $e->getMessage() : null
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
            return back()->with('error', 'El número de empleado no aparece en la lista de invitados.');
        }

        // Verificar si ya está registrado
        $existingAttendance = Attendance::where('event_id', $event->id)
            ->where('guest_id', $guest->id)
            ->first();

        if ($existingAttendance) {
            // Verificar si ha alcanzado el límite de escaneos
            if ($existingAttendance->scan_count >= Attendance::MAX_SCAN_COUNT) {
                return back()->with('error', 
                    "⚠️ LÍMITE EXCEDIDO: {$guest->full_name} ya utilizó sus {$existingAttendance->scan_count} escaneos permitidos. Primera asistencia: " . 
                    $existingAttendance->created_at->format('d/m/Y H:i:s')
                );
            }

            // Permitir registro adicional (acompañante)
            try {
                DB::beginTransaction();

                $existingAttendance->incrementScanCount();

                DB::commit();

                // Registrar en auditoría
                AuditLog::log(
                    action: 'manual_registration',
                    model: 'Attendance',
                    modelId: $existingAttendance->id,
                    description: "Registro manual adicional #{$existingAttendance->scan_count}: {$guest->full_name} ({$guest->numero_empleado}) - Acompañante"
                );

                return back()->with('success', 
                    "✓ Acompañante registrado para {$guest->full_name}. Registro #{$existingAttendance->scan_count} de " . Attendance::MAX_SCAN_COUNT
                );

            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Error al incrementar scan_count (manual): ' . $e->getMessage(), [
                    'exception' => $e,
                    'attendance_id' => $existingAttendance->id
                ]);
                
                return back()->with('error', 'Error al registrar el escaneo adicional.');
            }
        }

        // Registrar asistencia manual (primera vez)
        try {
            DB::beginTransaction();

            $attendance = Attendance::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'scanned_at' => now(),
                'scanned_by' => auth()->user()->name ?? 'Registro Manual',
                'scan_count' => 1,
                'last_scanned_at' => now(),
                'scan_metadata' => [
                    'method' => 'manual_registration',
                    'reason' => $request->reason ?? 'Sin especificar',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            DB::commit();

            // Registrar en auditoría
            AuditLog::log(
                action: 'manual_registration',
                model: 'Attendance',
                modelId: $attendance->id,
                description: "Registro manual: {$guest->full_name} ({$guest->numero_empleado}) en evento {$event->name}"
            );

            // Enviar email de confirmación de asistencia si el invitado tiene email
            if (!empty($guest->correo)) {
                try {
                    SendEmailJob::dispatch('attendance_confirmation', $guest, $event);
                } catch (\Exception $emailError) {
                    Log::warning('Error al despachar email de confirmación (registro manual): ' . $emailError->getMessage());
                }
            }

            // Crear participaciones automáticamente para todos los premios activos del evento
            RaffleEntryHelper::createAutoEntriesForGuest($guest, $event, $this->raffleService);

            return back()->with('success', 
                "¡Bienvenido {$guest->full_name}! Asistencia registrada exitosamente."
            );

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al registrar asistencia manual: ' . $e->getMessage(), [
                'exception' => $e,
                'event_id' => $event->id,
                'guest_id' => $guest->id ?? null,
                'employee_number' => $request->employee_number
            ]);

            return back()->with('error', 'Error al registrar la asistencia. Intente nuevamente.');
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
                        'work_area' => $attendance->guest->puesto,
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
                ->selectRaw('guests.puesto, COUNT(*) as count')
                ->groupBy('guests.puesto')
                ->orderByDesc('count')
                ->pluck('count', 'puesto')
        ];

        return Inertia::render('Events/Attendance/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->event_date->format('d/m/Y H:i'),
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