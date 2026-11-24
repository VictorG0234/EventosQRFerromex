<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\RaffleAppearanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Throwable;

class RaffleSettingsController extends Controller
{
    private const DIRECTORIES = [
        'background' => 'raffle-backgrounds',
        'logo' => 'raffle-logos',
        'audio' => 'raffle-audio',
    ];

    public function edit(Event $event, RaffleAppearanceService $appearance)
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Raffle/Settings', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'settings' => $appearance->payloadFor($event),
        ]);
    }

    public function update(Request $request, Event $event, RaffleAppearanceService $appearance)
    {
        $this->authorize('update', $event);

        $hex = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $validated = $request->validate([
            'title' => 'required|string|max:80',
            'primary_color' => $hex,
            'prize_color' => $hex,
            'accent_color' => $hex,
            'background' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'audio' => 'nullable|file|mimes:mp3,mpga,wav,ogg|max:10240',
            'clear_background' => 'sometimes|boolean',
            'clear_logo' => 'sometimes|boolean',
            'clear_audio' => 'sometimes|boolean',
            'sound_enabled' => 'required|boolean',
            'confetti_enabled' => 'required|boolean',
            'confetti_style' => 'required|in:'.implode(',', RaffleAppearanceService::CONFETTI_STYLES),
            'confetti_colors' => 'required|array|min:1|max:5',
            'confetti_colors.*' => $hex,
            'confetti_duration' => 'required|integer|min:2|max:20',
        ]);

        $current = $appearance->settingsFor($event);
        $newSettings = $current;
        $toDelete = [];
        $stored = [];

        try {
            foreach (RaffleAppearanceService::FILE_FIELDS as $field) {
                $key = $field.'_path';

                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store(self::DIRECTORIES[$field].'/'.$event->id, 'public');
                    if ($path === false) {
                        throw new \RuntimeException("No se pudo guardar el archivo: {$field}.");
                    }
                    $stored[] = $path;
                    $newSettings[$key] = $path;
                    $current[$key] && $toDelete[] = $current[$key];
                } elseif ($request->boolean('clear_'.$field)) {
                    $newSettings[$key] = null;
                    $current[$key] && $toDelete[] = $current[$key];
                }
            }

            $newSettings = array_merge($newSettings, [
                'title' => $validated['title'],
                'primary_color' => $validated['primary_color'],
                'prize_color' => $validated['prize_color'],
                'accent_color' => $validated['accent_color'],
                'sound_enabled' => (bool) $validated['sound_enabled'],
                'confetti_enabled' => (bool) $validated['confetti_enabled'],
                'confetti_style' => $validated['confetti_style'],
                'confetti_colors' => array_values($validated['confetti_colors']),
                'confetti_duration' => (int) $validated['confetti_duration'],
            ]);

            $event->update(['raffle_settings' => $newSettings]);
        } catch (Throwable $exception) {
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach ($toDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('events.raffle.settings.edit', $event)
            ->with('success', 'La personalización de la rifa se guardó correctamente.');
    }
}
