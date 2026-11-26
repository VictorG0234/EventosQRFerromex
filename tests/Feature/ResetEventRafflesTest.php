<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\GeneralRafflePrize;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\RaffleLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetEventRafflesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create(['name' => 'Organizador', 'email' => $email, 'password' => bcrypt('password')]);
    }

    private function fixture(User $user, string $suffix): array
    {
        $event = Event::create([
            'user_id' => $user->id,
            'name' => "Evento {$suffix}",
            'event_date' => '2026-12-01',
        ]);
        $guest = Guest::create([
            'event_id' => $event->id,
            'compania' => 'FERROMEX',
            'numero_empleado' => $suffix,
            'nombre_completo' => 'Invitado',
            'correo' => "invitado{$suffix}@example.com",
            'puesto' => 'Analista',
            'localidad' => 'CDMX',
        ]);
        Attendance::create(['event_id' => $event->id, 'guest_id' => $guest->id, 'scanned_at' => now()]);
        $prize = Prize::create(['event_id' => $event->id, 'name' => 'Premio', 'stock' => 0, 'active' => true]);
        $general = Prize::create(['event_id' => $event->id, 'name' => 'Rifa General', 'stock' => 999, 'active' => true]);
        $importedPrize = GeneralRafflePrize::create(['event_id' => $event->id, 'name' => 'Premio general']);
        $event->update(['imex_prize_id' => $prize->id]);

        foreach ([$prize, $general] as $rafflePrize) {
            RaffleEntry::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'prize_id' => $rafflePrize->id,
                'general_raffle_prize_id' => $rafflePrize->id === $general->id ? $importedPrize->id : null,
                'status' => 'won',
                'participated_at' => now(),
                'drawn_at' => now(),
                'prize_delivered' => true,
            ]);
            RaffleLog::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'guest_id' => $guest->id,
                'prize_id' => $rafflePrize->id,
                'raffle_type' => $rafflePrize->id === $general->id ? 'general' : 'public',
                'confirmed' => true,
            ]);
        }

        return [$event, $guest, $prize, $general, $importedPrize];
    }

    public function test_owner_can_reset_only_the_selected_event_and_preserve_its_data(): void
    {
        $owner = $this->user('owner@example.com');
        [$event, $guest, $prize, $general, $importedPrize] = $this->fixture($owner, '1');
        [$otherEvent, , $otherPrize, $otherGeneral] = $this->fixture($owner, '2');

        RaffleEntry::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'prize_id' => $prize->id,
            'status' => 'pending',
            'participated_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('events.raffle.reset', $event), ['confirmed' => true])
            ->assertRedirect(route('events.raffle.index', $event))
            ->assertSessionHas('success');

        $this->assertSame(0, $event->raffleEntries()->count());
        $this->assertSame(0, RaffleLog::where('event_id', $event->id)->count());
        $this->assertSame(1, $prize->fresh()->stock);
        $this->assertSame(999, $general->fresh()->stock);
        $this->assertNull($event->fresh()->imex_prize_id);
        $this->assertDatabaseHas('guests', ['id' => $guest->id]);
        $this->assertDatabaseHas('attendances', ['event_id' => $event->id, 'guest_id' => $guest->id]);
        $this->assertDatabaseHas('prizes', ['id' => $prize->id, 'event_id' => $event->id]);
        $this->assertDatabaseHas('prizes', ['id' => $general->id, 'event_id' => $event->id]);
        $this->assertDatabaseHas('general_raffle_prizes', ['id' => $importedPrize->id]);
        $this->assertSame(1, $event->generalRafflePrizes()->whereDoesntHave('raffleEntry')->count());
        $this->assertSame(2, $otherEvent->raffleEntries()->count());
        $this->assertSame(2, RaffleLog::where('event_id', $otherEvent->id)->count());
        $this->assertSame(0, $otherPrize->fresh()->stock);
        $this->assertSame(999, $otherGeneral->fresh()->stock);
        $this->assertSame($otherPrize->id, $otherEvent->fresh()->imex_prize_id);

        $this->post(route('events.raffle.reset', $event), ['confirmed' => true])
            ->assertRedirect(route('events.raffle.index', $event));
        $this->assertSame(1, $prize->fresh()->stock);
    }

    public function test_reset_requires_confirmation_and_event_ownership(): void
    {
        $owner = $this->user('owner@example.com');
        [$event, , $prize] = $this->fixture($owner, '1');
        $other = $this->user('other@example.com');

        $this->actingAs($owner)->post(route('events.raffle.reset', $event))
            ->assertSessionHasErrors('confirmed');
        $this->actingAs($other)->post(route('events.raffle.reset', $event), ['confirmed' => true])
            ->assertForbidden();

        $this->assertSame(2, $event->raffleEntries()->count());
        $this->assertSame(2, RaffleLog::where('event_id', $event->id)->count());
        $this->assertSame(0, $prize->fresh()->stock);
    }

    public function test_failed_reset_rolls_back_changes_and_reports_an_error(): void
    {
        $owner = $this->user('owner@example.com');
        [$event, , $prize] = $this->fixture($owner, '1');

        DB::statement("CREATE TRIGGER fail_raffle_log_delete BEFORE DELETE ON raffle_logs BEGIN SELECT RAISE(FAIL, 'test failure'); END");

        $this->actingAs($owner)->post(route('events.raffle.reset', $event), ['confirmed' => true])
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertSame(2, $event->raffleEntries()->count());
        $this->assertSame(2, RaffleLog::where('event_id', $event->id)->count());
        $this->assertSame(0, $prize->fresh()->stock);
        $this->assertSame($prize->id, $event->fresh()->imex_prize_id);
    }
}
