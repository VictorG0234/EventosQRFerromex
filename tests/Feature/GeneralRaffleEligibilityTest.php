<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\User;
use App\Services\RaffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralRaffleEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_raffle_winner_is_not_eligible_for_general_raffle(): void
    {
        $owner = User::create([
            'name' => 'Organizador',
            'email' => 'organizador@example.com',
            'password' => bcrypt('password'),
        ]);
        $event = Event::create([
            'user_id' => $owner->id,
            'name' => 'Evento',
            'event_date' => '2026-12-01',
        ]);

        $guests = collect(['Ganador', 'Elegible'])->map(function ($name, $index) use ($event) {
            $guest = Guest::create([
                'event_id' => $event->id,
                'compania' => 'FERROMEX',
                'numero_empleado' => (string) ($index + 1),
                'nombre_completo' => $name,
                'correo' => "invitado{$index}@example.com",
                'puesto' => 'Analista',
                'localidad' => 'CDMX',
                'descripcion' => 'General',
            ]);
            Attendance::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'scanned_at' => now(),
            ]);

            return $guest;
        });

        $prize = Prize::create([
            'event_id' => $event->id,
            'name' => 'Premio público',
            'stock' => 1,
            'active' => true,
        ]);
        RaffleEntry::create([
            'event_id' => $event->id,
            'guest_id' => $guests[0]->id,
            'prize_id' => $prize->id,
            'status' => 'won',
            'participated_at' => now(),
            'drawn_at' => now(),
        ]);

        $eligibleIds = app(RaffleService::class)
            ->getEligibleGuestsForGeneralRaffle($event)
            ->pluck('id')
            ->all();

        $this->assertNotContains($guests[0]->id, $eligibleIds);
        $this->assertContains($guests[1]->id, $eligibleIds);
    }
}
