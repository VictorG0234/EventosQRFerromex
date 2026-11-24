<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RaffleCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private function eventWithOwner(string $email = 'owner@example.com'): array
    {
        $user = User::create(['name' => 'Owner', 'email' => $email, 'password' => bcrypt('password')]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Evento', 'event_date' => '2026-12-01', 'status' => 'active']);

        return [$user, $event];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Gran Sorteo',
            'primary_color' => '#112233',
            'prize_color' => '#445566',
            'accent_color' => '#778899',
            'sound_enabled' => 0,
            'confetti_enabled' => 1,
            'confetti_style' => 'fireworks',
            'confetti_colors' => ['#ff0000', '#00ff00'],
            'confetti_duration' => 8,
        ], $overrides);
    }

    public function test_owner_can_customize_raffle_and_public_screen_receives_settings(): void
    {
        Storage::fake('public');
        [$user, $event] = $this->eventWithOwner();

        $this->actingAs($user)->get(route('events.raffle.settings.edit', $event))->assertOk();

        $this->post(route('events.raffle.settings.update', $event), $this->payload([
            'background' => UploadedFile::fake()->image('bg.png', 1600, 900),
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            'audio' => UploadedFile::fake()->create('sound.mp3', 100, 'audio/mpeg'),
        ]))->assertRedirect(route('events.raffle.settings.edit', $event));

        $settings = $event->fresh()->raffle_settings;
        $this->assertSame('Gran Sorteo', $settings['title']);
        $this->assertSame('#112233', $settings['primary_color']);
        $this->assertSame('fireworks', $settings['confetti_style']);
        $this->assertSame(['#ff0000', '#00ff00'], $settings['confetti_colors']);
        $this->assertFalse($settings['sound_enabled']);
        Storage::disk('public')->assertExists($settings['background_path']);
        Storage::disk('public')->assertExists($settings['logo_path']);
        Storage::disk('public')->assertExists($settings['audio_path']);

        $this->get(route('events.draw.cards', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('raffleSettings.title', 'Gran Sorteo')
                ->where('raffleSettings.primary_color', '#112233'));
    }

    public function test_replacing_and_clearing_files_removes_old_ones(): void
    {
        Storage::fake('public');
        [$user, $event] = $this->eventWithOwner();
        $this->actingAs($user);

        $this->post(route('events.raffle.settings.update', $event), $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
        ]));
        $first = $event->fresh()->raffle_settings['logo_path'];

        $this->post(route('events.raffle.settings.update', $event), $this->payload([
            'logo' => UploadedFile::fake()->image('logo2.png', 300, 100),
        ]));
        $second = $event->fresh()->raffle_settings['logo_path'];
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->post(route('events.raffle.settings.update', $event), $this->payload(['clear_logo' => 1]));
        $this->assertNull($event->fresh()->raffle_settings['logo_path']);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_invalid_values_are_rejected(): void
    {
        [$user, $event] = $this->eventWithOwner();

        $this->actingAs($user)
            ->post(route('events.raffle.settings.update', $event), $this->payload([
                'primary_color' => 'red; background:url(x)',
                'confetti_style' => 'laser',
                'confetti_colors' => [],
                'confetti_duration' => 99,
            ]))
            ->assertSessionHasErrors(['primary_color', 'confetti_style', 'confetti_colors', 'confetti_duration']);
    }

    public function test_non_owner_cannot_customize_raffle(): void
    {
        [, $event] = $this->eventWithOwner();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => bcrypt('password')]);

        $this->actingAs($other)->get(route('events.raffle.settings.edit', $event))->assertForbidden();
        $this->post(route('events.raffle.settings.update', $event), $this->payload())->assertForbidden();
    }
}
