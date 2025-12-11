<?php

namespace App\Helpers;

use App\Models\Prize;
use App\Models\Guest;
use Illuminate\Support\Collection;

class RaffleRulesHelper
{
    /**
     * Verificar si un invitado puede participar en rifa pública
     */
    public static function canParticipateInPublicRaffle(Guest $guest, Prize $prize): bool
    {
        // REGLA 10: Si Compañía del Guest es INV no puede participar
        if ($guest->compania === 'INV') {
            return false;
        }

        // REGLA 3, 5, 6, 7, 8: Excluir descripciones específicas
        $excludedDescriptions = ['Ganadores previos', 'Nuevo ingreso', 'Directores', 'No participa'];
        if (in_array($guest->descripcion, $excludedDescriptions)) {
            return false;
        }

        // REGLA 2 y 11: Si es Automovil, excluir IMEX y Subdirectores
        if (strtolower($prize->name) === 'automovil') {
            if ($guest->compania === 'IMEX' || $guest->descripcion === 'Subdirectores') {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar si un invitado puede participar en rifa general
     */
    public static function canParticipateInGeneralRaffle(Guest $guest): bool
    {
        // REGLA 8: Debe tener asistencia
        if (!$guest->attendance) {
            return false;
        }

        // REGLA 3: Solo "General" o "Subdirectores"
        if (!in_array($guest->descripcion, ['General', 'Subdirectores', 'IMEX'])) {
            return false;
        }

        // REGLA 9: No puede ser INV
        if ($guest->compania === 'INV') {
            return false;
        }

        return true;
    }

    /**
     * Verificar si un invitado ya ganó otro premio (REGLA 4)
     */
    public static function hasWonOtherPrize(Guest $guest, ?int $excludePrizeId = null): bool
    {
        $query = \App\Models\RaffleEntry::where('guest_id', $guest->id)
            ->where('status', 'won');

        if ($excludePrizeId) {
            $query->where('prize_id', '!=', $excludePrizeId);
        }

        return $query->exists();
    }

    /**
     * Verificar si ya existe un ganador IMEX en el evento
     */
    public static function hasIMEXWinnerInEvent(int $eventId): bool
    {
        return \App\Models\RaffleEntry::whereHas('prize', function ($q) use ($eventId) {
            $q->where('event_id', $eventId);
        })
        ->whereHas('guest', function ($q) {
            $q->where('compania', 'IMEX');
        })
        ->where('status', 'won')
        ->exists();
    }
}

