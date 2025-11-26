<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Services\RaffleService;

class RaffleEntryHelper
{
    /**
     * Crear participaciones automáticas para un invitado en todos los premios activos
     */
    public static function createAutoEntriesForGuest(Guest $guest, Event $event, RaffleService $raffleService): int
    {
        $totalCreated = 0;
        
        try {
            $activePrizes = $event->prizes()->where('active', true)->get();
            
            foreach ($activePrizes as $prize) {
                // Verificar si el invitado es elegible para este premio
                $eligibleGuests = $raffleService->getEligibleGuests($prize, 'general');
                
                if ($eligibleGuests->contains('id', $guest->id)) {
                    // Verificar si ya tiene una entrada para este premio
                    $existingEntry = RaffleEntry::where('guest_id', $guest->id)
                        ->where('prize_id', $prize->id)
                        ->first();
                    
                    // Si no tiene entrada, crearla
                    if (!$existingEntry) {
                        RaffleEntry::enterRaffle($guest, $prize, [
                            'auto_entered' => true,
                            'entered_by' => 'attendance_scan',
                            'attendance_id' => $guest->attendance?->id
                        ]);
                        $totalCreated++;
                    }
                }
            }
        } catch (\Exception $e) {
            // Error al crear participaciones automáticas, pero no fallar el proceso principal
        }
        
        return $totalCreated;
    }
}

