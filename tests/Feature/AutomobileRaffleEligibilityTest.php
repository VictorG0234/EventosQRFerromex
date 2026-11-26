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
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AutomobileRaffleEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private function setupRaffle(): array
    {
        $user = User::create(['name' => 'Organizador', 'email' => 'owner@example.com', 'password' => bcrypt('password')]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Evento', 'event_date' => '2026-12-01']);
        $automobile = Prize::create(['event_id' => $event->id, 'name' => 'Automovil', 'stock' => 1, 'active' => true]);
        $otherPrize = Prize::create(['event_id' => $event->id, 'name' => 'Bicicleta', 'stock' => 1, 'active' => true]);

        $guests = collect([
            '  PARTICIPA en premios   NO en auto  ',
            'Participa en todo',
        ])->map(function ($category, $index) use ($event) {
            $guest = Guest::create([
                'event_id' => $event->id,
                'compania' => 'FERROMEX',
                'numero_empleado' => (string) ($index + 1),
                'nombre_completo' => "Invitado {$index}",
                'correo' => "invitado{$index}@example.com",
                'puesto' => 'Analista',
                'localidad' => 'CDMX',
                'descripcion' => 'General',
                'categoria_rifa' => $category,
            ]);
            Attendance::create(['event_id' => $event->id, 'guest_id' => $guest->id, 'scanned_at' => now()]);

            return $guest;
        });

        return [$user, $event, $automobile, $otherPrize, $guests[0], $guests[1]];
    }

    public function test_category_is_excluded_from_car_candidates_but_allowed_for_other_prizes(): void
    {
        [$user, $event, $automobile, $otherPrize, $excluded, $eligible] = $this->setupRaffle();
        $service = app(RaffleService::class);

        $this->assertSame([$eligible->id], $service->getEligibleGuests($automobile, 'public')->pluck('id')->all());
        $this->assertSame([$eligible->id], $service->getEligibleGuests($automobile, 'general')->pluck('id')->all());
        $this->assertContains($excluded->id, $service->getEligibleGuests($otherPrize, 'public')->pluck('id')->all());

        $this->actingAs($user)->get(route('events.draw.cards', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('prizes.0.eligible_count', 1)
                ->where('prizes.1.eligible_count', 2));
    }

    public function test_existing_pending_entry_cannot_win_car_in_either_draw_path(): void
    {
        [, , $automobile, , $excluded, $eligible] = $this->setupRaffle();
        $service = app(RaffleService::class);
        RaffleEntry::enterRaffle($excluded, $automobile);
        RaffleEntry::enterRaffle($eligible, $automobile);

        $temporary = $service->selectWinnerTemporary($automobile, 'public');
        $this->assertTrue($temporary['success']);
        $this->assertSame($eligible->id, $temporary['winner']->guest_id);

        $result = $service->drawRaffle($automobile, 1, false, 'general');
        $this->assertTrue($result['success']);
        $this->assertSame($eligible->id, $result['winners'][0]->guest_id);
    }

    public function test_excluded_entry_cannot_be_confirmed_or_selected_manually_for_car(): void
    {
        [$user, $event, $automobile, , $excluded] = $this->setupRaffle();
        $entry = RaffleEntry::enterRaffle($excluded, $automobile);

        $this->actingAs($user)->post(route('events.raffle.confirm-winner', [$event, $automobile]), [
            'entry_id' => $entry->id,
            'send_notification' => false,
        ])->assertStatus(422);
        $this->post(route('events.raffle.select-winner', [$event, $automobile]), [
            'guest_id' => $excluded->id,
            'send_notification' => false,
        ])->assertSessionHas('error');

        $this->assertSame('pending', $entry->fresh()->status);
        $this->assertSame(1, $automobile->fresh()->stock);
    }

    public function test_simulated_attendance_does_not_create_car_entry_for_excluded_category(): void
    {
        [, $event, $automobile, $otherPrize, $excluded, $eligible] = $this->setupRaffle();
        $event->attendances()->delete();

        $this->artisan('attendance:mark-percentage', ['--event' => $event->id, '--percentage' => 100])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('raffle_entries', ['prize_id' => $automobile->id, 'guest_id' => $excluded->id]);
        $this->assertDatabaseHas('raffle_entries', ['prize_id' => $automobile->id, 'guest_id' => $eligible->id]);
        $this->assertDatabaseHas('raffle_entries', ['prize_id' => $otherPrize->id, 'guest_id' => $excluded->id]);
    }

    public function test_attendance_import_does_not_create_car_entry_for_excluded_category(): void
    {
        [, $event, $automobile, $otherPrize, $excluded, $eligible] = $this->setupRaffle();
        $event->attendances()->delete();
        $file = UploadedFile::fake()->createWithContent('attendances.txt', "1\n2\n");

        $this->artisan('attendance:import-ids', [
            'event_id' => $event->id,
            'file' => $file->getRealPath(),
            '--create-raffle-entries' => true,
        ])->expectsConfirmation('¿Deseas continuar con la creación de asistencias?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('raffle_entries', ['prize_id' => $automobile->id, 'guest_id' => $excluded->id]);
        $this->assertDatabaseHas('raffle_entries', ['prize_id' => $automobile->id, 'guest_id' => $eligible->id]);
        $this->assertDatabaseHas('raffle_entries', ['prize_id' => $otherPrize->id, 'guest_id' => $excluded->id]);
    }
}
