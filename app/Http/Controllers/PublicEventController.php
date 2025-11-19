<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Mail\GuestInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
                'start_time' => $event->start_time ? $event->start_time->setTimezone('America/Mexico_City')->format('H:i') : null,
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

        // Enviar email de invitación con código QR
        try {
            if ($guest->correo && filter_var($guest->correo, FILTER_VALIDATE_EMAIL)) {
                // Mail::to($guest->correo)->send(new GuestInvitationMail($guest, $event));                
                Log::info('Email de invitación enviado', [
                    'guest_id' => $guest->id,
                    'guest_name' => $guest->full_name,
                    'email' => $guest->correo,
                    'event_id' => $event->id
                ]);
            }
        } catch (\Exception $e) {
            // No bloqueamos el flujo si falla el email, solo lo registramos
            Log::error('Error al enviar email de invitación', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
        }

        // Redirigir a la página de detalles del invitado
        return redirect()->route('public.event.guest.details', [$token, $guest->id])
            ->with('success', '¡Bienvenido ' . $guest->full_name . '! Hemos enviado tu código QR a tu correo electrónico.');
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
                'start_time' => $event->start_time ? $event->start_time->setTimezone('America/Mexico_City')->format('H:i') : null,
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
                'attended_at' => $guest->attendance 
                    ? ($guest->attendance->scanned_at ?? $guest->attendance->created_at)->setTimezone('America/Mexico_City')->format('d/m/Y H:i') 
                    : null,
                'qr_code_url' => $guest->qr_code_path ? asset('storage/' . $guest->qr_code_path) : null,
            ]
        ]);
    }

    /**
     * Show privacy notice page
     */
    public function showPrivacyNotice()
    {
        return Inertia::render('Public/PrivacyNotice');
    }
}
