<?php

namespace App\Helpers;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
    /**
     * Calcular estadísticas de asistencia para un evento
     */
    public static function calculateAttendanceStatistics(Event $event): array
    {
        $totalGuests = $event->guests()->count();
        $totalAttendances = $event->attendances()->count();
        $attendanceRate = $totalGuests > 0 
            ? round(($totalAttendances / $totalGuests) * 100, 2) 
            : 0;

        return [
            'total_guests' => $totalGuests,
            'total_attendances' => $totalAttendances,
            'pending_guests' => $totalGuests - $totalAttendances,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Obtener estadísticas de ganadores excluyendo rifa general
     */
    public static function getWinnersStatistics(Event $event): array
    {
        $generalPrizeId = DB::table('prizes')
            ->where('event_id', $event->id)
            ->where('name', 'Rifa General')
            ->value('id');

        $winnersQuery = DB::table('raffle_entries')
            ->where('event_id', $event->id)
            ->where('status', 'won');

        if ($generalPrizeId) {
            $winnersQuery->where('prize_id', '!=', $generalPrizeId);
        }

        $totalWinners = (int) $winnersQuery
            ->selectRaw('COUNT(DISTINCT guest_id) as count')
            ->first()
            ->count ?? 0;

        $totalParticipants = (int) DB::table('raffle_entries')
            ->join('guests', 'raffle_entries.guest_id', '=', 'guests.id')
            ->where('raffle_entries.event_id', $event->id)
            ->selectRaw('COUNT(DISTINCT guests.numero_empleado) as count')
            ->first()
            ->count ?? 0;

        return [
            'total_winners' => $totalWinners,
            'total_participants' => $totalParticipants,
        ];
    }

    /**
     * Obtener asistencia por hora
     */
    public static function getHourlyAttendance(Event $event): array
    {
        $grouped = $event->attendances()
            ->get()
            ->groupBy(function($attendance) {
                $timestamp = $attendance->scanned_at ?? $attendance->created_at;
                return \Carbon\Carbon::parse($timestamp)
                    ->setTimezone('America/Mexico_City')
                    ->format('H');
            });
        
        return $grouped->map(function($group) {
            return $group->count();
        })->sortKeys()->toArray();
    }

    /**
     * Obtener asistencia por nivel de puesto (para dashboard)
     */
    public static function getAttendanceByJobLevel(Event $event): array
    {
        return $event->attendances()
            ->join('guests', 'attendances.guest_id', '=', 'guests.id')
            ->selectRaw('guests.nivel_de_puesto, COUNT(*) as count')
            ->groupBy('guests.nivel_de_puesto')
            ->pluck('count', 'nivel_de_puesto')
            ->toArray();
    }

    /**
     * Obtener asistencia por puesto (para reporte de estadísticas)
     */
    public static function getAttendanceByWorkArea(Event $event): array
    {
        return $event->attendances()
            ->join('guests', 'attendances.guest_id', '=', 'guests.id')
            ->selectRaw('guests.puesto, COUNT(*) as count')
            ->groupBy('guests.puesto')
            ->pluck('count', 'puesto')
            ->toArray();
    }

    /**
     * Formatear fecha para mostrar
     */
    public static function formatEventDate(Event $event): string
    {
        $eventDate = $event->event_date;
        $date = is_string($eventDate) 
            ? \Carbon\Carbon::parse($eventDate)->format('d/m/Y')
            : $eventDate->format('d/m/Y');
            
        if ($event->start_time) {
            $time = is_string($event->start_time) 
                ? $event->start_time 
                : $event->start_time->format('H:i');
            $date .= ' ' . $time;
        }
        return $date;
    }

    /**
     * Obtener estadísticas completas del evento (usado en dashboard - usa nivel_de_puesto)
     */
    public static function getCompleteEventStatistics(Event $event): array
    {
        $attendanceStats = self::calculateAttendanceStatistics($event);
        $winnersStats = self::getWinnersStatistics($event);
        
        // Excluir el premio especial "Rifa General" del conteo
        $prizesExcludingGeneral = $event->prizes->filter(function ($prize) {
            return trim($prize->name) !== 'Rifa General';
        });
        
        // Contar entradas activas en rifas
        $activeRaffleEntries = DB::table('raffle_entries')
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'won'])
            ->count();
        
        return [
            'overview' => array_merge($attendanceStats, [
                'total_prizes' => $prizesExcludingGeneral->count(),
                'total_prize_stock' => $prizesExcludingGeneral->sum('stock'),
                'total_winners' => $winnersStats['total_winners'],
                'total_participants' => $winnersStats['total_participants'],
                'active_raffle_entries' => $activeRaffleEntries,
            ]),
            'prizes_by_category' => $prizesExcludingGeneral->groupBy('category')->map->sum('stock'),
            'hourly_attendance' => self::getHourlyAttendance($event),
            'attendance_by_work_area' => self::getAttendanceByJobLevel($event),
        ];
    }

    /**
     * Obtener estadísticas completas para reporte (usa puesto)
     */
    public static function getCompleteEventStatisticsForReport(Event $event): array
    {
        $attendanceStats = self::calculateAttendanceStatistics($event);
        $winnersStats = self::getWinnersStatistics($event);
        
        // Excluir el premio especial "Rifa General" del conteo
        $prizesExcludingGeneral = $event->prizes->filter(function ($prize) {
            return trim($prize->name) !== 'Rifa General';
        });
        
        // Contar entradas activas en rifas
        $activeRaffleEntries = DB::table('raffle_entries')
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'won'])
            ->count();
        
        return [
            'overview' => array_merge($attendanceStats, [
                'total_prizes' => $prizesExcludingGeneral->count(),
                'total_prize_stock' => $prizesExcludingGeneral->sum('stock'),
                'total_winners' => $winnersStats['total_winners'],
                'total_participants' => $winnersStats['total_participants'],
                'active_raffle_entries' => $activeRaffleEntries,
            ]),
            'prizes_by_category' => $prizesExcludingGeneral->groupBy('category')->map->sum('stock'),
            'hourly_attendance' => self::getHourlyAttendance($event),
            'attendance_by_work_area' => self::getAttendanceByWorkArea($event),
        ];
    }

    /**
     * Formatear asistencias para mostrar
     */
    public static function formatAttendancesForDisplay($attendances): array
    {
        return $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'guest_name' => $attendance->guest->full_name,
                'employee_number' => $attendance->guest->numero_empleado,
                'work_area' => $attendance->guest->puesto,
                'attended_at' => \Carbon\Carbon::parse($attendance->scanned_at ?? $attendance->created_at)
                    ->setTimezone('America/Mexico_City')
                    ->format('d/m/Y H:i:s'),
            ];
        })->toArray();
    }
}

