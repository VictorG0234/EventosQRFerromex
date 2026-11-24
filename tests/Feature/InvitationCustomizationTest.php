<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Mail\GuestInvitationMail;
use App\Services\InvitationImageService;
use Tests\TestCase;

class InvitationCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_owner_can_save_invitation_design_and_email_copy(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Event Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);
        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Annual Event',
            'event_date' => '2026-12-01',
        ]);

        $this->actingAs($user)
            ->get(route('events.invitation.edit', $event))
            ->assertOk();

        $response = $this->post(route('events.invitation.update', $event), [
            'image' => UploadedFile::fake()->image('invitation.png', 714, 1179),
            'email_subject' => 'Nos vemos en el evento',
            'email_message' => 'Te esperamos.',
            'qr_x' => 10,
            'qr_y' => 10,
            'qr_size' => 30,
        ]);

        $response->assertRedirect(route('events.invitation.edit', $event));

        $settings = $event->fresh()->invitation_settings;
        $this->assertSame('Nos vemos en el evento', $settings['email_subject']);
        $this->assertSame('Te esperamos.', $settings['email_message']);
        $this->assertEquals(10, $settings['qr_x']);
        $this->assertEquals(10, $settings['qr_y']);
        $this->assertEquals(30, $settings['qr_size']);
        Storage::disk('public')->assertExists($settings['image_path']);

        $qrPath = UploadedFile::fake()->image('qr.png', 300, 300)->store('qr-codes', 'public');
        $guest = Guest::create([
            'event_id' => $event->id,
            'compania' => 'FERROMEX',
            'numero_empleado' => '12345',
            'nombre_completo' => 'Invitado de prueba',
            'correo' => 'guest@example.com',
            'puesto' => 'Analista',
            'localidad' => 'Ciudad de México',
            'qr_code' => 'QR-1234567890',
            'qr_code_path' => $qrPath,
        ]);

        $mail = new GuestInvitationMail($guest, $event->fresh());
        $html = $mail->render();
        $generatedImagePath = (new InvitationImageService())->generateInvitationWithQR($guest);

        $this->assertSame('Nos vemos en el evento', $mail->envelope()->subject);
        $this->assertStringContainsString('Te esperamos.', $html);
        Storage::disk('public')->assertExists($generatedImagePath);
        $this->assertCount(1, $mail->attachments());
    }

    public function test_user_cannot_customize_another_users_event_invitation(): void
    {
        $owner = User::create([
            'name' => 'Event Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);
        $event = Event::create([
            'user_id' => $owner->id,
            'name' => 'Annual Event',
            'event_date' => '2026-12-01',
        ]);

        $this->actingAs($otherUser)
            ->post(route('events.invitation.update', $event), [
                'email_subject' => 'Unauthorized change',
                'qr_x' => 10,
                'qr_y' => 10,
                'qr_size' => 30,
            ])
            ->assertForbidden();

        $this->assertNull($event->fresh()->invitation_settings);
    }

    public function test_public_guest_can_download_invitation_image(): void
    {
        Storage::fake('public');

        $user = User::create(['name' => 'Owner', 'email' => 'o2@example.com', 'password' => bcrypt('password')]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Evento', 'event_date' => '2026-12-01', 'status' => 'active']);
        $qrPath = UploadedFile::fake()->image('qr.png', 300, 300)->store('qr-codes', 'public');
        $guest = Guest::create([
            'event_id' => $event->id,
            'compania' => 'FERROMEX',
            'numero_empleado' => '123',
            'nombre_completo' => 'Test Guest',
            'correo' => 'guest@example.com',
            'puesto' => 'Analista',
            'localidad' => 'CDMX',
            'qr_code_path' => $qrPath,
        ]);

        $response = $this->get(route('public.event.guest.invitation', [$event->public_token, $guest->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_simulate_attendance_marks_requested_percentage(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'o3@example.com', 'password' => bcrypt('password')]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Evento', 'event_date' => '2026-12-01', 'status' => 'active']);
        for ($i = 1; $i <= 20; $i++) {
            Guest::create([
                'event_id' => $event->id, 'compania' => 'F', 'numero_empleado' => (string) $i,
                'nombre_completo' => "G{$i}", 'correo' => "g{$i}@example.com", 'puesto' => 'P', 'localidad' => 'L',
            ]);
        }

        $this->actingAs($user)->post(route('events.guests.simulate-attendance', $event), ['percentage' => 85])->assertRedirect();
        $this->assertSame(17, $event->guests()->whereHas('attendance')->count());

        $this->post(route('events.guests.simulate-attendance', $event), ['percentage' => 100])->assertRedirect();
        $this->assertSame(20, $event->guests()->whereHas('attendance')->count());
    }
}
