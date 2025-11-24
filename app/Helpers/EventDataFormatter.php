<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Attendance;
use App\Models\Guest;

class EventDataFormatter
{
    /**
     * Formatear datos del evento para Inertia
     */
    public static function formatEventForInertia(Event $event): array
    {
        return [
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
        ];
    }

    /**
     * Formatear asistencia para mostrar
     */
    public static function formatAttendance(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'guest_name' => $attendance->guest->full_name,
            'employee_number' => $attendance->guest->numero_empleado,
            'work_area' => $attendance->guest->puesto,
            'attended_at' => \Carbon\Carbon::parse($attendance->scanned_at ?? $attendance->created_at)
                ->setTimezone('America/Mexico_City')
                ->format('d/m/Y H:i:s'),
            'time_ago' => $attendance->created_at->diffForHumans(),
        ];
    }

    /**
     * Formatear invitado para mostrar
     */
    public static function formatGuest(Guest $guest): array
    {
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
        ];
    }
}

