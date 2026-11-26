<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GeneralRafflePrize;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GeneralRafflePrizeImportTest extends TestCase
{
    use RefreshDatabase;

    private function eventFor(User $user): Event
    {
        return Event::create([
            'user_id' => $user->id,
            'name' => 'Evento',
            'event_date' => '2026-12-01',
        ]);
    }

    private function user(string $email): User
    {
        return User::create(['name' => 'Organizador', 'email' => $email, 'password' => bcrypt('password')]);
    }

    private function csv(string $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'premios.csv',
            "Titulo,Descripcion,Categoria,Cantidad,Valor,Activo\n".$rows
        );
    }

    public function test_owner_imports_general_prizes_in_order_without_creating_public_prizes(): void
    {
        $owner = $this->user('owner@example.com');
        $event = $this->eventFor($owner);
        $otherEvent = $this->eventFor($owner);

        $this->actingAs($owner)->get(route('events.draw.general.prizes.import', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Prizes/Import')->where('generalRaffle', true));

        $this->post(route('events.prizes.preview', $event), [
            'csv_file' => $this->csv("Bicicleta,Azul,Equipo,2,1000,Si\nReloj,Digital,Equipo,1,500,No\n"),
        ])->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.sample_data.0.row_number', 2);

        $this->post(route('events.draw.general.prizes.import.process', $event), [
            'csv_file' => $this->csv("Bicicleta,Azul,Equipo,2,1000,Si\nReloj,Digital,Equipo,1,500,No\n"),
        ])->assertRedirect(route('events.draw.general', $event))
            ->assertSessionHas('success', 'Se importaron 3 premios para la rifa general.');

        $prizes = $event->generalRafflePrizes()->orderBy('id')->get();
        $this->assertSame(['Bicicleta', 'Bicicleta', 'Reloj'], $prizes->pluck('name')->all());
        $this->assertSame([true, true, false], $prizes->pluck('active')->all());
        $this->assertSame(0, Prize::count());
        $this->assertSame(0, $otherEvent->generalRafflePrizes()->count());

        $this->get(route('events.draw.general', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('general_prizes_count', 3)
                ->where('available_prizes_count', 2)
                ->missing('general_prizes'));
    }

    public function test_invalid_csv_does_not_import_any_rows(): void
    {
        $owner = $this->user('owner@example.com');
        $event = $this->eventFor($owner);

        $this->actingAs($owner)->post(route('events.prizes.preview', $event), [
            'csv_file' => $this->csv("Bicicleta,Azul,Equipo,1,1000,Si\nReloj,Digital,Equipo,0,500,Si\n"),
        ])->assertOk()->assertJsonPath('data.validation_summary.errors.0.row', 3);

        $this->actingAs($owner)->post(route('events.draw.general.prizes.import.process', $event), [
            'csv_file' => $this->csv("Bicicleta,Azul,Equipo,1,1000,Si\nReloj,Digital,Equipo,0,500,Si\n"),
        ])->assertSessionHasErrors('csv_file');

        $this->assertSame(0, GeneralRafflePrize::count());
    }

    public function test_other_user_cannot_access_or_import_general_prizes(): void
    {
        $owner = $this->user('owner@example.com');
        $event = $this->eventFor($owner);
        $other = $this->user('other@example.com');

        $this->actingAs($other)->get(route('events.draw.general.prizes.import', $event))->assertForbidden();
        $this->post(route('events.draw.general.prizes.import.process', $event), [
            'csv_file' => $this->csv("Bicicleta,Azul,Equipo,1,1000,Si\n"),
        ])->assertForbidden();
        $this->assertSame(0, GeneralRafflePrize::count());
    }
}
