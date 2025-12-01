<?php

namespace App\Mail;

use App\Models\Guest;
use App\Models\Event;
use App\Services\InvitationImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GuestInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Guest $guest,
        public Event $event
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu invitación para ' . $this->event->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Generar la imagen de invitación con QR
        $invitationService = new InvitationImageService();
        $invitationPath = $invitationService->generateInvitationWithQR($this->guest);
        
        return new Content(
            view: 'emails.invitation-image',
            with: [
                'guest' => $this->guest,
                'event' => $this->event,
                'invitationImageUrl' => asset('storage/' . $invitationPath),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        
        // Generar la imagen de invitación con QR
        try {
            $invitationService = new InvitationImageService();
            $invitationPath = $invitationService->generateInvitationWithQR($this->guest);
            
            // Adjuntar la imagen de invitación
            if (Storage::disk('public')->exists($invitationPath)) {
                $attachments[] = Attachment::fromStorageDisk('public', $invitationPath)
                    ->as('invitacion-' . $this->guest->numero_empleado . '.png')
                    ->withMime('image/png');
            }
        } catch (\Exception $e) {
            // Log del error pero no bloqueamos el envío
            \Log::error('Error generando imagen de invitación: ' . $e->getMessage());
        }
        
        return $attachments;
    }
}
