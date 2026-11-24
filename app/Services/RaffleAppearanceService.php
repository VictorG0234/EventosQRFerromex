<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;

class RaffleAppearanceService
{
    public const FILE_FIELDS = ['background', 'logo', 'audio'];

    public const CONFETTI_STYLES = ['snow', 'burst', 'fireworks'];

    public function defaults(): array
    {
        return [
            'title' => 'Sorteo de Fin de Año',
            'primary_color' => '#33529F',
            'prize_color' => '#B2D6EC',
            'accent_color' => '#ed2d36',
            'background_path' => null,
            'logo_path' => null,
            'audio_path' => null,
            'sound_enabled' => true,
            'confetti_enabled' => true,
            'confetti_style' => 'snow',
            'confetti_colors' => ['#ed2d36'],
            'confetti_duration' => 6,
        ];
    }

    public function settingsFor(Event $event): array
    {
        $stored = is_array($event->raffle_settings) ? $event->raffle_settings : [];

        return array_merge($this->defaults(), array_intersect_key($stored, $this->defaults()));
    }

    /** Settings plus resolved asset URLs, ready to be sent to the front end. */
    public function payloadFor(Event $event): array
    {
        $settings = $this->settingsFor($event);

        return $settings + [
            'background_url' => $this->urlFor($settings['background_path'], 'images/raffle-border-background.png'),
            'logo_url' => $this->urlFor($settings['logo_path'], 'images/logo-gm.png'),
            'audio_url' => $this->urlFor($settings['audio_path'], 'audio/rifa.mp3'),
            'default_background_url' => asset('images/raffle-border-background.png'),
            'default_logo_url' => asset('images/logo-gm.png'),
            'default_audio_url' => asset('audio/rifa.mp3'),
        ];
    }

    private function urlFor(?string $path, string $default): string
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($default);
    }
}
