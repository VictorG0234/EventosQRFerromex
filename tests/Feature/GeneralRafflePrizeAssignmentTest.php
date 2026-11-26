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

    public function test_general_raffle_excludes_public_winner_without_counting_them_toward_imex_limit(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $publicWinner = $this->guest($event, '1');
        $publicWinner->update(['categoria_rifa' => 'IMEX']);
        $publicPrize = Prize::create(['event_id' => $event->id, 'name' => 'Público', 'stock' => 1, 'active' => true]);
        RaffleEntry::enterRaffle($publicWinner, $publicPrize)->update(['status' => 'won', 'drawn_at' => now()]);
        $first = $this->guest($event, '2');
        $second = $this->guest($event, '3');
        $first->update(['categoria_rifa' => 'IMEX']);
        $second->update(['categoria_rifa' => 'IMEX']);
        $this->prize($event, 'Uno');
        $this->prize($event, 'Dos');

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 2])
            ->assertOk()->assertJsonPath('winners_count', 2);

        $winners = RaffleEntry::where('event_id', $event->id)
            ->whereNotNull('general_raffle_prize_id')
            ->pluck('guest_id')->all();
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $winners);
    }

    public function test_general_draw_limits_mixed_company_and_category_imex_to_two(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $publicWinner = $this->guest($event, '1', 'IMEX');
        $publicPrize = Prize::create(['event_id' => $event->id, 'name' => 'Público', 'stock' => 1, 'active' => true]);
        RaffleEntry::enterRaffle($publicWinner, $publicPrize)->update(['status' => 'won', 'drawn_at' => now()]);
        $otherEvent = $this->event($owner);
        $this->legacyWinner($otherEvent, $this->guest($otherEvent, '2', 'IMEX'));
        $this->guest($event, '3', ' imex ');
        $this->guest($event, '4')->update(['categoria_rifa' => ' iMeX ']);
        $this->guest($event, '5')->update(['categoria_rifa' => 'IMEX', 'descripcion' => 'Subdirectores']);
        $ordinaryOne = $this->guest($event, '6');
        $ordinaryTwo = $this->guest($event, '7');
        foreach (range(1, 4) as $number) {
            $this->prize($event, "Premio {$number}");
        }

        $this->actingAs($owner)->get(route('events.draw.general', $event))
            ->assertOk()->assertInertia(fn ($page) => $page->where('eligible_count', 4));
        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 4])
            ->assertOk()->assertJsonPath('winners_count', 4);

        $generalWinners = RaffleEntry::where('event_id', $event->id)
            ->whereNotNull('general_raffle_prize_id')->with('guest')->get();
        $service = app(RaffleService::class);
        $this->assertCount(2, $generalWinners->filter(fn ($entry) => $service->isImexGuest($entry->guest)));
        $this->assertContains($ordinaryOne->id, $generalWinners->pluck('guest_id')->all());
        $this->assertContains($ordinaryTwo->id, $generalWinners->pluck('guest_id')->all());
        $this->assertNotContains($publicWinner->id, $generalWinners->pluck('guest_id')->all());
    }

    public function test_following_draw_respects_existing_imex_winners_and_updates_available_count(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $first = $this->guest($event, '1', ' imex ');
        $second = $this->guest($event, '2');
        $second->update(['categoria_rifa' => 'IMEX']);
        $third = $this->guest($event, '3');
        $third->update(['categoria_rifa' => ' imEx ']);
        $normal = $this->guest($event, '4');
        foreach (range(1, 3) as $number) {
            $this->prize($event, "Premio {$number}");
        }

        $this->actingAs($owner)->postJson(route('events.draw.general.execute', $event), ['winners_count' => 2])
            ->assertOk();
        $this->get(route('events.draw.general', $event))
            ->assertOk()->assertInertia(fn ($page) => $page->where('eligible_count', 1));
        $this->postJson(route('events.draw.general.execute', $event), ['winners_count' => 1])
            ->assertOk()->assertJsonPath('winners.0.id', $normal->id);
        $this->assertSame(2, app(RaffleService::class)->countGeneralImexWinners($event->id));
    }

    public function test_confirmation_and_manual_selection_cannot_exceed_general_imex_limit(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $this->legacyWinner($event, $this->guest($event, '1', ' imex '));
        $second = $this->guest($event, '2');
        $second->update(['categoria_rifa' => 'IMEX']);
        $this->legacyWinner($event, $second);
        $candidate = $this->guest($event, '3');
        $candidate->update(['categoria_rifa' => 'IMEX']);
        $general = app(RaffleService::class)->getOrCreateGeneralRafflePrize($event);
        $entry = RaffleEntry::enterRaffle($candidate, $general);
        $error = 'Ya hay dos ganadores IMEX en la rifa general de este evento.';

        $this->actingAs($owner)->postJson(route('events.raffle.confirm-winner', [$event, $general]), [
            'entry_id' => $entry->id, 'send_notification' => false,
        ])->assertStatus(422)->assertJsonPath('message', $error);
        $this->post(route('events.raffle.select-winner', [$event, $general]), [
            'guest_id' => $candidate->id, 'send_notification' => false,
        ])->assertSessionHas('error', $error);
        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_generic_general_draw_and_temporary_selection_respect_available_imex_slots(): void
    {
        $event = $this->event($this->owner());
        $this->legacyWinner($event, $this->guest($event, '1', 'IMEX'));
        $categoryOne = $this->guest($event, '2');
        $categoryOne->update(['categoria_rifa' => 'IMEX']);
        $categoryTwo = $this->guest($event, '3');
        $categoryTwo->update(['categoria_rifa' => 'IMEX']);
        $normalOne = $this->guest($event, '4');
        $normalTwo = $this->guest($event, '5');
        $service = app(RaffleService::class);
        $general = $service->getOrCreateGeneralRafflePrize($event);
        foreach ([$categoryOne, $categoryTwo, $normalOne, $normalTwo] as $guest) {
            RaffleEntry::enterRaffle($guest, $general);
        }

        $draw = $service->drawRaffle($general, 3, false, 'general');
        $this->assertTrue($draw['success']);
        $this->assertCount(3, $draw['winners']);
        $this->assertContains($normalOne->id, collect($draw['winners'])->pluck('guest_id')->all());
        $this->assertContains($normalTwo->id, collect($draw['winners'])->pluck('guest_id')->all());
        $this->assertSame(2, $service->countGeneralImexWinners($event->id));

        $remainingImex = $this->guest($event, '6');
        $remainingImex->update(['categoria_rifa' => 'IMEX']);
        $remainingNormal = $this->guest($event, '7');
        RaffleEntry::enterRaffle($remainingImex, $general);
        RaffleEntry::enterRaffle($remainingNormal, $general);
        $temporary = $service->selectWinnerTemporary($general, 'general');
        $this->assertTrue($temporary['success']);
        $this->assertSame($remainingNormal->id, $temporary['winner']->guest_id);
    }

    public function test_replacement_does_not_exceed_general_imex_limit_and_frees_replaced_slot(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $imexOne = $this->guest($event, '1', ' imex ');
        $imexTwo = $this->guest($event, '2');
        $imexTwo->update(['categoria_rifa' => 'IMEX']);
        $this->legacyWinner($event, $imexOne);
        $secondWinner = $this->legacyWinner($event, $imexTwo);
        $secondWinner->update(['general_raffle_prize_id' => $this->prize($event, 'Segundo')->id]);
        $normal = $this->guest($event, '3');
        $normalWinner = $this->legacyWinner($event, $normal);
        $normalWinner->update(['general_raffle_prize_id' => $this->prize($event, 'Tercero')->id]);
        $imexCandidate = $this->guest($event, '4');
        $imexCandidate->update(['categoria_rifa' => ' imex ']);
        $normalCandidate = $this->guest($event, '5');

        $this->actingAs($owner)->postJson(route('events.draw.general.reselect', $event), ['guest_id' => $normal->id])
            ->assertOk()->assertJsonPath('winner.id', $normalCandidate->id);
        $normal->update(['categoria_rifa' => 'No Participa']);
        $this->postJson(route('events.draw.general.reselect', $event), ['guest_id' => $imexTwo->id])
            ->assertOk()->assertJsonPath('winner.id', $imexCandidate->id);
        $this->assertSame(2, app(RaffleService::class)->countGeneralImexWinners($event->id));
    }

    public function test_pending_legacy_prize_assignment_is_blocked_if_there_are_over_two_imex_winners(): void
    {
        $owner = $this->owner();
        $event = $this->event($owner);
        $first = $this->legacyWinner($event, $this->guest($event, '1', 'IMEX'));
        $second = $this->guest($event, '2');
        $second->update(['categoria_rifa' => 'IMEX']);
        $this->legacyWinner($event, $second);
        $this->legacyWinner($event, $this->guest($event, '3', ' imex '));
        $this->prize($event, 'Premio');

        $this->actingAs($owner)->post(route('events.draw.general.prizes.assign', $event))
            ->assertSessionHas('error', 'Hay más de dos ganadores IMEX registrados. Revisa los resultados antes de asignar premios pendientes.');
        $this->assertNull($first->fresh()->general_raffle_prize_id);
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
