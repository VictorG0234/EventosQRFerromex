<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\InvitationImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Throwable;

class InvitationController extends Controller
{
    public function edit(Event $event, InvitationImageService $invitationImageService)
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Invitation/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'settings' => $invitationImageService->settingsFor($event),
            'imageUrl' => $invitationImageService->imageUrlFor($event),
            'defaultImageUrl' => asset('images/emails/invitacion/Invitacion.png'),
            'qrPreviewUrl' => $invitationImageService->previewQrDataUri(),
        ]);
    }

    public function update(Request $request, Event $event, InvitationImageService $invitationImageService)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'clear_image' => 'sometimes|boolean',
            'email_subject' => 'required|string|max:255',
            'email_message' => 'nullable|string|max:5000',
            'qr_x' => 'required|numeric|min:0|max:100',
            'qr_y' => 'required|numeric|min:0|max:100',
            'qr_size' => 'required|numeric|min:5|max:100',
        ]);

        $currentSettings = $invitationImageService->settingsFor($event);
        if ($request->hasFile('image')) {
            $placementImagePath = $request->file('image')->getRealPath();
        } elseif ($request->boolean('clear_image')) {
            $placementImagePath = $invitationImageService->defaultImagePath();
        } else {
            $placementImagePath = $invitationImageService->baseImagePath($event, $currentSettings);
        }
        $invitationImageService->validateQrPlacement($validated, $placementImagePath);

        $oldImagePath = $currentSettings['image_path'];
        $newImagePath = $oldImagePath;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('invitation-backgrounds/'.$event->id, 'public');
            if ($newImagePath === false) {
                throw new \RuntimeException('No se pudo guardar la imagen base de la invitación.');
            }
        } elseif ($request->boolean('clear_image')) {
            $newImagePath = null;
        }

        $eventSettings = array_merge($currentSettings, [
            'image_path' => $newImagePath,
            'email_subject' => $validated['email_subject'],
            'email_message' => $validated['email_message'] ?? '',
            'qr_x' => (float) $validated['qr_x'],
            'qr_y' => (float) $validated['qr_y'],
            'qr_size' => (float) $validated['qr_size'],
        ]);

        try {
            $event->update(['invitation_settings' => $eventSettings]);
        } catch (Throwable $exception) {
            if ($newImagePath && $newImagePath !== $oldImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($oldImagePath && $oldImagePath !== $newImagePath) {
            if (!Storage::disk('public')->delete($oldImagePath)) {
                \Log::warning('No se pudo eliminar la imagen base anterior de la invitación.', [
                    'event_id' => $event->id,
                    'image_path' => $oldImagePath,
                ]);
            }
        }

        return redirect()
            ->route('events.invitation.edit', $event)
            ->with('success', 'La invitación se guardó correctamente.');
    }
}
