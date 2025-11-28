<?php

namespace App\Mail;

use App\Models\Guest;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GuestInvitationMail extends Mailable implements ShouldQueue
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
        return new Content(
            view: 'emails.concert-invitation',
            with: [
                'guest' => $this->guest,
                'event' => $this->event,
                'qrCodeUrl' => $this->guest->qr_code_path ? asset('storage/' . $this->guest->qr_code_path) : null,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        
        // Adjuntar el código QR si existe
        if ($this->guest->qr_code_path && Storage::disk('public')->exists($this->guest->qr_code_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->guest->qr_code_path)
                ->as('codigo-qr-' . $this->guest->numero_empleado . '.png')
                ->withMime('image/png');
        }
        
        return $attachments;
    }
}
