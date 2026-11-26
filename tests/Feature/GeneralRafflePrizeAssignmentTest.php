<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\GeneralRafflePrize;
use App\Models\Guest;
use App\Models\Prize;
use App\Models\RaffleEntry;
use App\Models\User;
use App\Services\RaffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralRafflePrizeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create(['name' => 'Organizador', 'email' => 'owner@example.com', 'password' => bcrypt('password')]);
    }

    private function event(User $owner): Event
    {
        return Event::create(['user_id' => $owner->id, 'name' => 'Evento', 'event_date' => '2026-12-01']);
    }

    private function guest(Event $event, string $number, string $company = 'FERROMEX'): Guest
    {
        $guest = Guest::create([
            'event_id' => $event->id,
            'compania' => $company,
            'numero_empleado' => $number,
            'nombre_completo' => "Invitado {$number}",
            'correo' => "invitado{$number}@example.com",
            'puesto' => 'Analista',
            'localidad' => 'CDMX',
            'descripcion' => 'General',
            'categoria_rifa' => 'Participa',
        ]);
        Attendance::create(['event_id' => $event->id, 'guest_id' => $guest->id, 'scanned_at' => now()]);

        return $guest;
    }

    private function prize(Event $event, string $name, bool $active = true): GeneralRafflePrize
    {
        return GeneralRafflePrize::create(['event_id' => $event->id, 'name' => $name, 'active' => $active]);
    }

    private function legacyWinner(Event $event, Guest $guest): RaffleEntry
    {
        $general = app(RaffleService::class)->getOrCreateGeneralRafflePrize($event);

        return RaffleEntry::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'prize_id' => $general->id,
            'status' => 'won',
            'position' => 1,
            'participated_at' => now(),
            'drawn_at' => now(),
        ]);
    }

    public function test_draw_assigns_distinct_active_prizes_and_persists_names(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $otherEvent = $this->event($owner);
        $firstGuest = $this->guest($event, '1');
        $this->guest($event, '2');
        $this->guest($event, '3');
        $publicPrize = Prize::create(['event_id' => $event->id, 'name' => 'Público', 'stock' => 0, 'active' => true]);
        RaffleEntry::create([
            'event_id' => $event->id, 'guest_id' => $firstGuest->id, 'prize_id' => $publicPrize->id,
            'status' => 'won', 'participated_at' => now(), 'drawn_at' => now(),
        ]);
        $first = $this->prize($event, 'Bicicleta');
        $second = $this->prize($event, 'Reloj');
        $inactive = $this->prize($event, 'Inactivo', false);
        $otherPrize = $this->prize($otherEvent, 'Otro evento');

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 2])
            ->assertOk()->assertJsonPath('winners_count', 2);

        $entries = RaffleEntry::where('event_id', $event->id)
            ->whereNotNull('general_raffle_prize_id')->with('generalRafflePrize')->get();
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $entries->pluck('general_raffle_prize_id')->all());
        $this->assertNotContains($firstGuest->id, $entries->pluck('guest_id')->all());
        $this->assertNotContains($inactive->id, $entries->pluck('general_raffle_prize_id')->all());
        $this->assertNotContains($otherPrize->id, $entries->pluck('general_raffle_prize_id')->all());
        $this->assertSame(3, $event->generalRafflePrizes()->count());

        $this->get(route('events.draw.general', $event))->assertOk()
            ->assertInertia(fn ($page) => $page->has('winners', 2)
                ->where('available_prizes_count', 0)
                ->where('winners.0.prize_name', $entries->sortBy('position')->first()->generalRafflePrize->name));
    }

    public function test_draw_cannot_select_more_winners_than_available_prizes_even_with_imex(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $this->guest($event, '1', 'IMEX');
        $this->guest($event, '2', 'IMEX');
        $this->prize($event, 'Único');

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 2])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(0, $event->raffleEntries()->where('status', 'won')->count());

        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])
            ->assertOk()->assertJsonPath('winners_count', 1)->assertJsonPath('winners.0.prize_name', 'Único');
        $this->assertSame(1, $event->raffleEntries()->where('status', 'won')->count());
    }

    public function test_subsequent_draws_do_not_reuse_prizes_or_previous_winners(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $this->guest($event, '1');
        $this->guest($event, '2');
        $this->guest($event, '3');
        $this->prize($event, 'Premio A');
        $this->prize($event, 'Premio B');

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])->assertOk();
        $firstWinner = RaffleEntry::where('event_id', $event->id)->where('status', 'won')->firstOrFail();
        $firstPrizeId = $firstWinner->general_raffle_prize_id;
        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])->assertOk();

        $winners = RaffleEntry::where('event_id', $event->id)->where('status', 'won')->get();
        $this->assertCount(2, $winners);
        $this->assertCount(2, $winners->pluck('guest_id')->unique());
        $this->assertCount(2, $winners->pluck('general_raffle_prize_id')->unique());
        $this->assertSame($firstPrizeId, $firstWinner->fresh()->general_raffle_prize_id);
        $this->assertSame(2, $event->generalRafflePrizes()->count());
        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])->assertStatus(422);
    }

    public function test_failed_redraw_does_not_remove_existing_assignments(): void
    {
        $event = $this->event($this->owner());
        $winner = $this->legacyWinner($event, $this->guest($event, '1'));
        $this->guest($event, '2');
        $prize = $this->prize($event, 'Premio');
        $winner->update(['general_raffle_prize_id' => $prize->id, 'prize_delivered' => true]);

        $result = app(RaffleService::class)->drawGeneralRaffle($event, 2, false, true);

        $this->assertFalse($result['success']);
        $this->assertSame('won', $winner->fresh()->status);
        $this->assertSame($prize->id, $winner->fresh()->general_raffle_prize_id);
        $this->assertTrue($winner->fresh()->prize_delivered);
    }

    public function test_existing_winners_can_receive_available_prizes_without_reassigning_previous_ones(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $winner = $this->legacyWinner($event, $this->guest($event, '1'));
        $secondWinner = $this->legacyWinner($event, $this->guest($event, '2'));
        $thirdGuest = $this->guest($event, '3');
        $prize = $this->prize($event, 'Primero');
        $this->prize($event, 'Inactivo', false);

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])
            ->assertStatus(422);
        $this->post(route('events.draw.general.prizes.assign', $event))->assertSessionHas('error');
        $this->assertSame($prize->id, $winner->fresh()->general_raffle_prize_id);
        $this->assertNull($secondWinner->fresh()->general_raffle_prize_id);

        $secondPrize = $this->prize($event, 'Segundo');
        $this->post(route('events.draw.general.prizes.assign', $event))->assertSessionHas('success');
        $this->assertSame($prize->id, $winner->fresh()->general_raffle_prize_id);
        $this->assertSame($secondPrize->id, $secondWinner->fresh()->general_raffle_prize_id);
        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])->assertStatus(422);
        $this->assertSame(0, RaffleEntry::where('guest_id', $thirdGuest->id)->where('status', 'won')->count());
    }

    public function test_existing_winners_are_assigned_prizes_in_random_order(): void
    {
        $event = $this->event($this->owner());
        $winners = collect(range(1, 5))->map(fn ($i) => $this->legacyWinner($event, $this->guest($event, (string) $i)));
        $prizes = collect(range(1, 5))->map(fn ($i) => $this->prize($event, "Premio {$i}"));
        $expected = $prizes->pluck('id')->shuffle(1234)->values()->all();
        $this->assertNotSame($prizes->pluck('id')->all(), $expected);

        mt_srand(1234);
        app(RaffleService::class)->assignPrizesToExistingGeneralWinners($event);
        mt_srand();

        $this->assertSame($expected, $winners->map(fn ($winner) => $winner->fresh()->general_raffle_prize_id)->all());
    }

    public function test_reselect_transfers_the_same_prize_and_failed_reselect_preserves_it(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $guest = $this->guest($event, '1');
        $winner = $this->legacyWinner($event, $guest);
        $prize = $this->prize($event, 'Premio');
        $winner->update(['general_raffle_prize_id' => $prize->id]);

        $this->actingAs($owner)->postJson(route('events.draw.general.reselect', $event), ['guest_id' => $guest->id])
            ->assertStatus(422);
        $this->assertSame('won', $winner->fresh()->status);
        $this->assertSame($prize->id, $winner->fresh()->general_raffle_prize_id);

        $replacement = $this->guest($event, '2');
        $this->postJson(route('events.draw.general.reselect', $event), ['guest_id' => $guest->id])
            ->assertOk()->assertJsonPath('winner.id', $replacement->id)->assertJsonPath('winner.prize_name', 'Premio');
        $this->assertNull($winner->fresh()->general_raffle_prize_id);
        $this->assertDatabaseHas('raffle_entries', [
            'event_id' => $event->id, 'guest_id' => $replacement->id,
            'status' => 'won', 'general_raffle_prize_id' => $prize->id,
        ]);
    }

    public function test_other_user_cannot_assign_existing_prizes(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $winner = $this->legacyWinner($event, $this->guest($event, '1'));
        $this->prize($event, 'Premio');
        $other = User::create(['name' => 'Otro', 'email' => 'other@example.com', 'password' => bcrypt('password')]);

        $this->actingAs($other)->post(route('events.draw.general.prizes.assign', $event))->assertForbidden();
        $this->assertNull($winner->fresh()->general_raffle_prize_id);
    }
}
