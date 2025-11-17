<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicEventController extends Controller
{
    /**
     * Show public registration form
     */
    public function showRegistrationForm(string $token)
    {
        $event = Event::where('public_token', $token)->firstOrFail();

        return Inertia::render('Public/EventRegistration', [
            'token' => $token,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'event_date' => $event->event_date->format('d/m/Y'),
                'start_time' => $event->start_time ? $event->start_time->format('H:i') : null,
                'location' => $event->location,
            ]
        ]);
    }

    /**
     * Validate guest credentials
     */
    public function validateGuest(Request $request, string $token)
    {
        $request->validate([
            'credentials' => 'required|string',
        ]);

        $event = Event::where('public_token', $token)->firstOrFail();

        // Buscar invitado que coincida con la combinación compania-numero_empleado
        $credentials = trim($request->credentials);
        
        // Intentar encontrar el invitado buscando en diferentes formatos
        $guest = Guest::where('event_id', $event->id)
            ->where(function($query) use ($credentials) {
                // Buscar por combinación exacta (ej: "Ferromex-12345")
                $query->whereRaw("CONCAT(compania, '-', numero_empleado) = ?", [$credentials])
                    // O por combinación sin guión (ej: "Ferromex12345")
                    ->orWhereRaw("CONCAT(compania, numero_empleado) = ?", [$credentials])
                    // O por combinación con espacio (ej: "Ferromex 12345")
                    ->orWhereRaw("CONCAT(compania, ' ', numero_empleado) = ?", [$credentials]);
            })
            ->first();

        if (!$guest) {
            return back()->withErrors([
                'credentials' => 'No se encontró ningún invitado con estas credenciales. Verifica que hayas ingresado correctamente tu Compañía y Número de Empleado.'
            ]);
        }

        // Redirigir a la página de detalles del invitado
        return redirect()->route('public.event.guest.details', [$token, $guest->id])
            ->with('success', '¡Bienvenido ' . $guest->full_name . '!');
    }

    /**
     * Show guest details after validation
     */
    public function showGuestDetails(string $token, int $guestId)
    {
        $event = Event::where('public_token', $token)->firstOrFail();
        $guest = Guest::where('event_id', $event->id)
            ->where('id', $guestId)
            ->with('attendance')
            ->firstOrFail();

        return Inertia::render('Public/GuestDetails', [
            'token' => $token,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'event_date' => $event->event_date->format('d/m/Y'),
                'start_time' => $event->start_time ? $event->start_time->format('H:i') : null,
                'location' => $event->location,
            ],
            'guest' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'compania' => $guest->compania,
                'numero_empleado' => $guest->numero_empleado,
                'correo' => $guest->correo,
                'puesto' => $guest->puesto,
                'localidad' => $guest->localidad,
                'categoria_rifa' => $guest->categoria_rifa,
                'has_attended' => $guest->has_attended,
                'attended_at' => $guest->attendance ? $guest->attendance->created_at->format('d/m/Y H:i') : null,
                'qr_code_url' => $guest->qr_code_path ? asset('storage/' . $guest->qr_code_path) : null,
            ]
        ]);
    }
}
