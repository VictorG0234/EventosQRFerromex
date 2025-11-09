<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestImportService;
use App\Services\QrCodeService;
use App\Services\EmailService;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
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
        $this->qrService = $qrService;
    }

    /**
     * Display a listing of guests for a specific event.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $guests = $event->guests()
            ->with(['attendance'])
            ->paginate(50)
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
                    'area_laboral' => $guest->area_laboral,
                    'premios_rifa' => $guest->premios_rifa,
                    'has_attended' => $guest->attendance !== null,
                    'attended_at' => $guest->attendance?->created_at?->format('d/m/Y H:i:s'),
                    'qr_code_path' => $guest->qr_code_path,
                ];
            });

        return Inertia::render('Events/Guests/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'guests_count' => $event->guests()->count(),
                'attendances_count' => $event->attendances()->count(),
            ],
            'guests' => $guests
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
            'nombre' => 'required|string|max:255',
            'apellido_p' => 'required|string|max:255',
            'apellido_m' => 'nullable|string|max:255',
            'numero_empleado' => 'required|string|max:50|unique:guests,numero_empleado,NULL,id,event_id,' . $event->id,
            'area_laboral' => 'required|string|max:255',
            'premios_rifa' => 'nullable|string|max:500',
        ]);

        $validated['event_id'] = $event->id;

        $guest = Guest::create($validated);

        // Enviar email de bienvenida si el invitado tiene email
        if (!empty($guest->email)) {
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
                'nombre' => $guest->nombre,
                'apellido_p' => $guest->apellido_p,
                'apellido_m' => $guest->apellido_m,
                'numero_empleado' => $guest->numero_empleado,
                'area_laboral' => $guest->area_laboral,
                'premios_rifa' => $guest->premios_rifa,
                'email' => $guest->email,
                'qr_code' => $guest->qr_code,
                'qr_code_path' => $guest->qr_code_path,
                'qr_code_data' => $guest->qr_code_data,
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
                'nombre' => $guest->nombre,
                'apellido_p' => $guest->apellido_p,
                'apellido_m' => $guest->apellido_m,
                'numero_empleado' => $guest->numero_empleado,
                'area_laboral' => $guest->area_laboral,
                'premios_rifa' => $guest->premios_rifa,
                'email' => $guest->email,
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
            'nombre' => 'required|string|max:255',
            'apellido_p' => 'required|string|max:255',
            'apellido_m' => 'nullable|string|max:255',
            'numero_empleado' => 'required|string|max:50|unique:guests,numero_empleado,' . $guest->id . ',id,event_id,' . $event->id,
            'area_laboral' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'premios_rifa' => 'nullable|array',
        ]);

        $guest->update($validated);

        // Regenerar QR code si cambió información crítica
        if ($guest->wasChanged(['nombre', 'apellido_p', 'numero_empleado'])) {
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

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $result = $this->importService->importFromCsv(
                $request->file('csv_file'),
                $event
            );

            return redirect()->route('events.guests.index', $event)
                ->with('success', "Importación completada: {$result['imported']} invitados importados, {$result['errors']} errores.");

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