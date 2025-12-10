<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestImportService;
use App\Services\QrCodeService;
use App\Services\EmailService;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    protected $importService;
    protected $qrService;
    protected $emailService;

    public function __construct(GuestImportService $importService, QrCodeService $qrService, EmailService $emailService)
    {
        $this->importService = $importService;
        $this->qrService = $qrService;
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of guests for a specific event.
     */
    public function index(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        // Obtener parámetros de búsqueda y filtro
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        // Construir query con filtros
        $query = $event->guests()->with(['attendance']);

        // Aplicar búsqueda si existe
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre_completo', 'like', '%' . $search . '%')
                  ->orWhere('numero_empleado', 'like', '%' . $search . '%')
                  ->orWhere('puesto', 'like', '%' . $search . '%')
                  ->orWhere('localidad', 'like', '%' . $search . '%');
            });
        }

        // Aplicar filtro de asistencia
        if ($filter === 'attended') {
            $query->has('attendance');
        } elseif ($filter === 'not_attended') {
            $query->doesntHave('attendance');
        }

        $guests = $query->paginate(50)
            ->withQueryString() // Mantener parámetros de búsqueda en paginación
            ->through(function ($guest) {
                // Generar QR si no existe
                if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
                    $this->qrService->generateQrCode($guest);
                    $guest->refresh();
                }
                
                return [
                    'id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'numero_empleado' => $guest->numero_empleado,
                    'compania' => $guest->compania,
                    'puesto' => $guest->puesto,
                    'localidad' => $guest->localidad,
                    'categoria_rifa' => $guest->categoria_rifa,
                    'has_attended' => $guest->attendance !== null,
                    'attended_at' => $guest->attendance?->created_at?->format('d/m/Y H:i:s'),
                    'qr_code_path' => $guest->qr_code_path,
                ];
            });

        // Calcular estadísticas totales (no solo de la página actual)
        $totalGuests = $event->guests()->count();
        $totalAttendances = $event->attendances()->count();
        $guestsWithAttendance = $event->guests()->has('attendance')->count();
        
        return Inertia::render('Events/Guests/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'guests_count' => $totalGuests,
                'attendances_count' => $totalAttendances,
                'guests_with_attendance' => $guestsWithAttendance,
            ],
            'guests' => $guests,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ]
        ]);
    }

    /**
     * Show the form for creating a new guest.
     */
    public function create(Event $event)
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Guests/Create', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ]
        ]);
    }

    /**
     * Store a newly created guest.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $validated = $request->validate([
            'compania' => 'required|string|max:255',
            'numero_empleado' => [
                'required',
                'string',
                'max:50',
                Rule::unique('guests')
                    ->where('event_id', $event->id)
                    ->where('compania', $request->compania)
            ],
            'nombre_completo' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'puesto' => 'required|string|max:255',
            'nivel_de_puesto' => 'nullable|string|max:255',
            'localidad' => 'required|string|max:255',
            'fecha_alta' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'categoria_rifa' => 'nullable|string|max:255',
        ]);

        $validated['event_id'] = $event->id;

        $guest = Guest::create($validated);

        // Generar código QR
        app(QrCodeService::class)->generateQrCode($guest);

        // Enviar email de bienvenida si el invitado tiene correo
        if (!empty($guest->correo)) {
            SendEmailJob::dispatch('welcome', $guest);
        }

        return redirect()->route('events.guests.index', $event)
            ->with('success', 'Invitado agregado exitosamente.');
    }

    /**
     * Display the specified guest.
     */
    public function show(Event $event, Guest $guest)
    {
        $this->authorize('view', $event);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        $guest->load(['attendance']);

        return Inertia::render('Events/Guests/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'guest' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'compania' => $guest->compania,
                'numero_empleado' => $guest->numero_empleado,
                'nombre_completo' => $guest->nombre_completo,
                'correo' => $guest->correo,
                'puesto' => $guest->puesto,
                'nivel_de_puesto' => $guest->nivel_de_puesto,
                'localidad' => $guest->localidad,
                'fecha_alta' => $guest->fecha_alta?->format('d/m/Y'),
                'descripcion' => $guest->descripcion,
                'categoria_rifa' => $guest->categoria_rifa,
                'qr_code' => $guest->qr_code,
                'qr_code_path' => $guest->qr_code_path,
                'has_attended' => $guest->attendance !== null,
                'attended_at' => $guest->attendance?->created_at?->format('d/m/Y H:i:s'),
                'created_at' => $guest->created_at->format('d/m/Y H:i:s'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified guest.
     */
    public function edit(Event $event, Guest $guest)
    {
        $this->authorize('view', $event);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        return Inertia::render('Events/Guests/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'guest' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'compania' => $guest->compania,
                'numero_empleado' => $guest->numero_empleado,
                'nombre_completo' => $guest->nombre_completo,
                'correo' => $guest->correo,
                'puesto' => $guest->puesto,
                'nivel_de_puesto' => $guest->nivel_de_puesto,
                'localidad' => $guest->localidad,
                'fecha_alta' => $guest->fecha_alta?->format('Y-m-d'),
                'descripcion' => $guest->descripcion,
                'categoria_rifa' => $guest->categoria_rifa,
            ]
        ]);
    }

    /**
     * Update the specified guest.
     */
    public function update(Request $request, Event $event, Guest $guest)
    {
        $this->authorize('view', $event);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'compania' => 'required|string|max:255',
            'numero_empleado' => [
                'required',
                'string',
                'max:50',
                Rule::unique('guests')
                    ->where('event_id', $event->id)
                    ->where('compania', $request->compania)
                    ->ignore($guest->id)
            ],
            'nombre_completo' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'puesto' => 'required|string|max:255',
            'nivel_de_puesto' => 'nullable|string|max:255',
            'localidad' => 'required|string|max:255',
            'fecha_alta' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'categoria_rifa' => 'nullable|string|max:255',
        ]);

        $guest->update($validated);

        // Regenerar QR code si cambió información crítica
        if ($guest->wasChanged(['nombre_completo', 'numero_empleado'])) {
            $this->qrService->generateQrCode($guest);
        }

        return redirect()->route('events.guests.show', [$event, $guest])
            ->with('success', 'Invitado actualizado exitosamente.');
    }

    /**
     * Remove the specified guest.
     */
    public function destroy(Event $event, Guest $guest)
    {
        $this->authorize('view', $event);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        $guestName = $guest->full_name;
        
        // Eliminar archivo QR si existe
        if ($guest->qr_code_path && Storage::disk('public')->exists($guest->qr_code_path)) {
            Storage::disk('public')->delete($guest->qr_code_path);
        }

        $guest->delete();

        return redirect()->route('events.guests.index', $event)
            ->with('success', "Invitado '{$guestName}' eliminado exitosamente.");
    }

    /**
     * Show CSV import form.
     */
    public function importForm(Event $event)
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Guests/Import', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'guests_count' => $event->guests()->count(),
            ]
        ]);
    }

    /**
     * Process CSV import.
     */
    public function import(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        // Aumentar tiempo de ejecución para archivos grandes
        set_time_limit(300); // 5 minutos
        ini_set('max_execution_time', '300');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $result = $this->importService->importFromCsv(
                $request->file('csv_file'),
                $event
            );

            $errorsCount = count($result['errors']);
            $warningsCount = count($result['warnings'] ?? []);
            $message = "Importación completada: {$result['imported']} invitados importados.";
            
            if ($errorsCount > 0) {
                $message .= " {$errorsCount} registro(s) con errores.";
            }
            
            if ($warningsCount > 0) {
                $message .= " {$warningsCount} advertencia(s) (fechas convertidas automáticamente).";
            }

            return redirect()->route('events.guests.index', $event)
                ->with($errorsCount > 0 ? 'warning' : 'success', $message);

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', 'Error en la validación del archivo CSV.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Preview CSV data before import.
     */
    public function preview(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $preview = $this->importService->previewCsv($request->file('csv_file'));

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Download guest QR code.
     */
    public function downloadQr(Event $event, Guest $guest)
    {
        $this->authorize('view', $event);
        
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        // Si no tiene QR o el archivo no existe, generarlo
        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            $this->qrService->generateQrCode($guest);
            $guest->refresh();
        }

        // Verificar nuevamente después de generar
        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            abort(404, 'No se pudo generar el código QR');
        }

        $fileName = "QR_{$guest->numero_empleado}_{$guest->nombre}.png";
        
        // Descargar el archivo desde Storage
        return Storage::disk('public')->download($guest->qr_code_path, $fileName);
    }
}