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

class PublicRaffleImexLimitTest extends TestCase
{
    use RefreshDatabase;

    private function setupEvent(): array
    {
        $user = User::create(['name' => 'Organizador', 'email' => 'owner@example.com', 'password' => bcrypt('password')]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Evento', 'event_date' => '2026-12-01']);
        return [$user, $event];
    }

    private function guest(Event $event, string $number, string $company, string $category = 'Participa en todo'): Guest
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
            'categoria_rifa' => $category,
        ]);
        Attendance::create(['event_id' => $event->id, 'guest_id' => $guest->id, 'scanned_at' => now()]);
        return $guest;
    }

    private function prize(Event $event, string $name, int $stock = 1): Prize
    {
        return Prize::create(['event_id' => $event->id, 'name' => $name, 'stock' => $stock, 'active' => true]);
    }

    private function win(Guest $guest, Prize $prize): RaffleEntry
    {
        $entry = RaffleEntry::enterRaffle($guest, $prize);
        $entry->update(['status' => 'won', 'drawn_at' => now()]);
        return $entry;
    }

    public function test_existing_public_imex_winner_blocks_other_imex_on_assigned_prize(): void
    {
        [, $event] = $this->setupEvent();
        $previousPrize = $this->prize($event, 'Premio anterior');
        $assignedPrize = $this->prize($event, 'Premio asignado');
        $event->update(['imex_prize_id' => $assignedPrize->id]);
        $this->win($this->guest($event, '1', '  imex  '), $previousPrize);
        $imex = $this->guest($event, '2', 'IMEX');
        $other = $this->guest($event, '3', 'FERROMEX');
        RaffleEntry::enterRaffle($imex, $assignedPrize);
        RaffleEntry::enterRaffle($other, $assignedPrize);
        $service = app(RaffleService::class);

        $temporary = $service->selectWinnerTemporary($assignedPrize);
        $this->assertTrue($temporary['success']);
        $this->assertSame($other->id, $temporary['winner']->guest_id);
        $result = $service->drawRaffle($assignedPrize, 1, false, 'public');
        $this->assertTrue($result['success']);
        $this->assertSame($other->id, $result['winners'][0]->guest_id);
    }

    public function test_category_imex_is_selected_for_assigned_prize_and_limited_in_multi_winner_draw(): void
    {
        [, $event] = $this->setupEvent();
        $prize = $this->prize($event, 'Premio', 2);
        $event->update(['imex_prize_id' => $prize->id]);
        $byCategory = $this->guest($event, '1', 'FERROMEX', ' imEx ');
        RaffleEntry::enterRaffle($byCategory, $prize);
        $other = $this->guest($event, '2', 'FERROMEX');
        RaffleEntry::enterRaffle($other, $prize);
        $service = app(RaffleService::class);

        $temporary = $service->selectWinnerTemporary($prize);
        $this->assertTrue($temporary['success']);
        $this->assertSame($byCategory->id, $temporary['winner']->guest_id);

        RaffleEntry::enterRaffle($this->guest($event, '3', 'IMEX'), $prize);
        $result = $service->drawRaffle($prize, 2, false, 'public');
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['winners']);
        $this->assertContains($other->id, collect($result['winners'])->pluck('guest_id')->all());
    }

    public function test_category_imex_previous_winner_blocks_company_and_category_imex(): void
    {
        [$user, $event] = $this->setupEvent();
        $previousPrize = $this->prize($event, 'Anterior');
        $prize = $this->prize($event, 'Premio');
        $event->update(['imex_prize_id' => $prize->id]);
        $this->win($this->guest($event, '1', 'FERROMEX', ' imex '), $previousPrize);
        $byCompany = $this->guest($event, '2', 'IMEX');
        $byCategory = $this->guest($event, '3', 'FERROMEX', 'IMEX');
        $companyEntry = RaffleEntry::enterRaffle($byCompany, $prize);
        $categoryEntry = RaffleEntry::enterRaffle($byCategory, $prize);
        $other = $this->guest($event, '4', 'FERROMEX');
        RaffleEntry::enterRaffle($other, $prize);

        $this->actingAs($user)->postJson(route('events.raffle.confirm-winner', [$event, $prize]), [
            'entry_id' => $companyEntry->id, 'send_notification' => false,
        ])->assertStatus(422)->assertJsonPath('message', 'Ya existe un ganador IMEX en la rifa pública de este evento.');
        $this->post(route('events.raffle.select-winner', [$event, $prize]), [
            'guest_id' => $byCategory->id, 'send_notification' => false,
        ])->assertSessionHas('error', 'Ya existe un ganador IMEX en la rifa pública de este evento.');
        $service = app(RaffleService::class);
        $temporary = $service->selectWinnerTemporary($prize);
        $this->assertTrue($temporary['success']);
        $this->assertSame($other->id, $temporary['winner']->guest_id);
        $result = $service->drawRaffle($prize, 1, false, 'public');
        $this->assertTrue($result['success']);
        $this->assertSame($other->id, $result['winners'][0]->guest_id);
        $this->assertNotSame('won', $categoryEntry->fresh()->status);
    }

    public function test_imex_category_cannot_win_public_prize_after_winning_general_raffle(): void
    {
        [$user, $event] = $this->setupEvent();
        $guest = $this->guest($event, '1', 'FERROMEX', 'IMEX');
        $this->win($guest, $this->prize($event, 'Rifa General'));
        $prize = $this->prize($event, 'Premio');
        $entry = RaffleEntry::enterRaffle($guest, $prize);

        $this->actingAs($user)->postJson(route('events.raffle.confirm-winner', [$event, $prize]), [
            'entry_id' => $entry->id, 'send_notification' => false,
        ])->assertStatus(422)->assertJsonPath('message', 'Este participante IMEX ya ganó un premio en el evento.');
        $this->post(route('events.raffle.select-winner', [$event, $prize]), [
            'guest_id' => $guest->id, 'send_notification' => false,
        ])->assertSessionHas('error', 'Este participante IMEX ya ganó un premio en el evento.');
        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_imex_category_cannot_be_confirmed_or_manually_selected_for_general_after_public_win(): void
    {
        [$user, $event] = $this->setupEvent();
        $guest = $this->guest($event, '1', 'FERROMEX', 'IMEX');
        $this->win($guest, $this->prize($event, 'Premio'));
        $general = $this->prize($event, 'Rifa General');
        $entry = RaffleEntry::enterRaffle($guest, $general);

        $this->actingAs($user)->postJson(route('events.raffle.confirm-winner', [$event, $general]), [
            'entry_id' => $entry->id, 'send_notification' => false,
        ])->assertStatus(422)->assertJsonPath('message', 'Este participante IMEX ya ganó un premio en el evento.');
        $this->post(route('events.raffle.select-winner', [$event, $general]), [
            'guest_id' => $guest->id, 'send_notification' => false,
        ])->assertSessionHas('error', 'Este participante IMEX ya ganó un premio en el evento.');
        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_public_card_flow_selects_and_confirms_a_non_imex_after_an_imex_has_won(): void
    {
        [$user, $event] = $this->setupEvent();
        $previousPrize = $this->prize($event, 'Anterior');
        $prize = $this->prize($event, 'Premio');
        $event->update(['imex_prize_id' => $prize->id]);
        $this->win($this->guest($event, '1', ' IMEX '), $previousPrize);
        RaffleEntry::enterRaffle($this->guest($event, '2', 'IMEX'), $prize);
        $guest = $this->guest($event, '3', 'FERROMEX');
        $entry = RaffleEntry::enterRaffle($guest, $prize);

        $this->actingAs($user)->postJson(route('events.raffle.draw-single', [$event, $prize]), [])
            ->assertOk()->assertJsonPath('winner.id', $guest->id)->assertJsonPath('entry_id', $entry->id);
        $this->postJson(route('events.raffle.confirm-winner', [$event, $prize]), [
            'entry_id' => $entry->id, 'send_notification' => false,
        ])->assertOk();
        $this->assertSame('won', $entry->fresh()->status);
    }

    public function test_public_confirmation_rechecks_limit_and_ignores_general_and_other_events(): void
    {
        [$user, $event] = $this->setupEvent();
        $otherEvent = Event::create(['user_id' => $user->id, 'name' => 'Otro', 'event_date' => '2026-12-01']);
        $this->win($this->guest($otherEvent, '1', 'FERROMEX', 'IMEX'), $this->prize($otherEvent, 'Otro premio'));
        $general = $this->prize($event, 'Rifa General');
        $this->win($this->guest($event, '2', 'FERROMEX', 'IMEX'), $general);
        $firstPrize = $this->prize($event, 'Primero');
        $secondPrize = $this->prize($event, 'Segundo');
        $first = RaffleEntry::enterRaffle($this->guest($event, '3', 'imex'), $firstPrize);
        $second = RaffleEntry::enterRaffle($this->guest($event, '4', 'FERROMEX', ' IMEX '), $secondPrize);

        $this->actingAs($user)->postJson(route('events.raffle.confirm-winner', [$event, $firstPrize]), [
            'entry_id' => $first->id, 'send_notification' => false,
        ])->assertOk();
        $this->postJson(route('events.raffle.confirm-winner', [$event, $secondPrize]), [
            'entry_id' => $second->id, 'send_notification' => false,
        ])->assertStatus(422)->assertJsonPath('message', 'Ya existe un ganador IMEX en la rifa pública de este evento.');

        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame(1, $secondPrize->fresh()->stock);
        $this->assertSame('won', $first->fresh()->status);
    }

    public function test_manual_winner_selection_cannot_bypass_event_imex_limit(): void
    {
        [$user, $event] = $this->setupEvent();
        $previousPrize = $this->prize($event, 'Anterior');
        $prize = $this->prize($event, 'Premio');
        $this->win($this->guest($event, '1', 'ImEx'), $previousPrize);
        $guest = $this->guest($event, '2', ' IMEX ');
        $entry = RaffleEntry::enterRaffle($guest, $prize);

        $this->actingAs($user)->post(route('events.raffle.select-winner', [$event, $prize]), [
            'guest_id' => $guest->id, 'send_notification' => false,
        ])->assertSessionHas('error', 'Ya existe un ganador IMEX en la rifa pública de este evento.');
        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_manual_selection_allows_first_imex_winner(): void
    {
        [$user, $event] = $this->setupEvent();
        $prize = $this->prize($event, 'Premio');
        $guest = $this->guest($event, '1', ' IMEX ');
        $entry = RaffleEntry::enterRaffle($guest, $prize);

        $this->actingAs($user)->post(route('events.raffle.select-winner', [$event, $prize]), [
            'guest_id' => $guest->id, 'send_notification' => false,
        ])->assertSessionHas('success');
        $this->assertSame('won', $entry->fresh()->status);
    }

    public function test_public_prize_draw_endpoint_uses_public_rules(): void
    {
        [$user, $event] = $this->setupEvent();
        $previousPrize = $this->prize($event, 'Anterior');
        $prize = $this->prize($event, 'Premio');
        $event->update(['imex_prize_id' => $prize->id]);
        $this->win($this->guest($event, '1', 'IMEX'), $previousPrize);
        RaffleEntry::enterRaffle($this->guest($event, '2', 'IMEX'), $prize);
        $other = $this->guest($event, '3', 'FERROMEX');
        RaffleEntry::enterRaffle($other, $prize);

        $this->actingAs($user)->postJson(route('events.raffle.draw', [$event, $prize]), [
            'quantity' => 1, 'send_notification' => false,
        ])->assertOk()->assertJsonPath('winners.0.guest.id', $other->id)
            ->assertJsonPath('remaining_stock', 0);
    }

    public function test_multi_winner_public_draw_selects_at_most_one_imex(): void
    {
        [, $event] = $this->setupEvent();
        $prize = $this->prize($event, 'Premio', 2);
        $event->update(['imex_prize_id' => $prize->id]);
        RaffleEntry::enterRaffle($this->guest($event, '1', 'IMEX'), $prize);
        RaffleEntry::enterRaffle($this->guest($event, '2', 'IMEX'), $prize);
        $other = $this->guest($event, '3', 'FERROMEX');
        RaffleEntry::enterRaffle($other, $prize);

        $result = app(RaffleService::class)->drawRaffle($prize, 2, false, 'public');
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['winners']);
        $this->assertCount(1, collect($result['winners'])->filter(fn ($entry) => strtoupper(trim($entry->guest->compania)) === 'IMEX'));
        $this->assertContains($other->id, collect($result['winners'])->pluck('guest_id')->all());
    }

    public function test_imex_cannot_be_confirmed_or_manually_selected_for_automobile(): void
    {
        [$user, $event] = $this->setupEvent();
        $prize = $this->prize($event, 'Automovil');
        $guest = $this->guest($event, '1', 'IMEX');
        $entry = RaffleEntry::enterRaffle($guest, $prize);

        $this->actingAs($user)->postJson(route('events.raffle.confirm-winner', [$event, $prize]), [
            'entry_id' => $entry->id, 'send_notification' => false,
        ])->assertStatus(422);
        $this->post(route('events.raffle.select-winner', [$event, $prize]), [
            'guest_id' => $guest->id, 'send_notification' => false,
        ])->assertSessionHas('error');
        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_failed_redraw_preserves_previous_imex_winner(): void
    {
        [$user, $event] = $this->setupEvent();
        $prize = $this->prize($event, 'Premio');
        $event->update(['imex_prize_id' => $prize->id]);
        $guest = $this->guest($event, '1', 'IMEX');
        $previousWinner = $this->win($guest, $prize);
        $prize->update(['stock' => 0]);
        $guest->update(['categoria_rifa' => 'No Participa']);

        $this->actingAs($user)->postJson(route('events.raffle.draw-single', [$event, $prize]), [
            'card_index' => 0, 'reset_previous' => true,
        ])->assertStatus(422);
        $this->assertSame('won', $previousWinner->fresh()->status);
        $this->assertSame(0, $prize->fresh()->stock);
    }
}
