<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvitationImageService
{
    private const DEFAULT_IMAGE = 'images/emails/invitacion/Invitacion.png';

    public function settingsFor(Event $event): array
    {
        return array_merge([
            'image_path' => null,
            'qr_x' => 23.4,
            'qr_y' => 21.2,
            'qr_size' => 53.2,
            'email_subject' => 'Tu invitación para '.$event->name,
            'email_message' => '',
        ], $event->invitation_settings ?? []);
    }

    public function imageUrlFor(Event $event): string
    {
        $imagePath = $this->settingsFor($event)['image_path'];

        if ($imagePath) {
            return Storage::disk('public')->url($imagePath).'?v='.($event->updated_at?->timestamp ?? time());
        }

        return asset(self::DEFAULT_IMAGE);
    }

    public function baseImagePath(Event $event, ?array $settings = null): string
    {
        $imagePath = ($settings ?? $this->settingsFor($event))['image_path'];
        $baseImagePath = $imagePath
            ? Storage::disk('public')->path($imagePath)
            : $this->defaultImagePath();

        if (!is_file($baseImagePath)) {
            throw new \RuntimeException('No se encontró la imagen base de invitación.');
        }

        return $baseImagePath;
    }

    public function defaultImagePath(): string
    {
        return public_path(self::DEFAULT_IMAGE);
    }

    public function previewQrDataUri(): string
    {
        $qrCode = QrCode::create('INVITACION-PREVIA')
            ->setSize(300)
            ->setMargin(10);

        return (new PngWriter())->write($qrCode)->getDataUri();
    }

    public function validateQrPlacement(array $settings, string $imagePath): void
    {
        $image = Image::make($imagePath);
        $maxX = 100 - (float) $settings['qr_size'];
        $maxY = 100 - ((float) $settings['qr_size'] * $image->width() / $image->height());

        if ((float) $settings['qr_x'] > $maxX || (float) $settings['qr_y'] > $maxY) {
            throw ValidationException::withMessages([
                'qr_size' => 'El código QR debe quedar completamente dentro de la imagen base.',
            ]);
        }
    }

    public function generateInvitationWithQR(Guest $guest): string
    {
        $event = $guest->event;

        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            throw new \RuntimeException('El invitado no tiene código QR generado.');
        }

        $settings = $this->settingsFor($event);
        $invitationImage = $this->composeInvitation($guest, $event, $settings);
        $filename = 'invitations/invitation_'.$guest->id.'_'.bin2hex(random_bytes(8)).'.png';

        Storage::disk('public')->makeDirectory('invitations');
        $invitationImage->save(Storage::disk('public')->path($filename), 90);

        return $filename;
    }

    public function downloadInvitationWithQR(Guest $guest)
    {
        $event = $guest->event;

        if (!$guest->qr_code_path || !Storage::disk('public')->exists($guest->qr_code_path)) {
            throw new \RuntimeException('El invitado no tiene código QR generado.');
        }

        $invitationImage = $this->composeInvitation($guest, $event, $this->settingsFor($event));

        return $invitationImage->response('png', 90);
    }

    private function composeInvitation(Guest $guest, Event $event, array $settings)
    {
        $invitationImage = Image::make($this->baseImagePath($event, $settings));
        $qrPath = Storage::disk('public')->path($guest->qr_code_path);
        $qrSize = (int) round($invitationImage->width() * (float) $settings['qr_size'] / 100);
        $qrImage = Image::make($qrPath)->resize($qrSize, $qrSize);
        $qrX = (int) round($invitationImage->width() * (float) $settings['qr_x'] / 100);
        $qrY = (int) round($invitationImage->height() * (float) $settings['qr_y'] / 100);

        $invitationImage->insert($qrImage, 'top-left', $qrX, $qrY);

        return $invitationImage;
    }
}
