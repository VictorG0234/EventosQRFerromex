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

    private ?string $invitationPath = null;

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
        $settings = $this->event->invitation_settings ?? [];

        return new Envelope(
            subject: $settings['email_subject'] ?? 'Tu invitación para '.$this->event->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $settings = $this->event->invitation_settings ?? [];
        $invitationPath = $this->getInvitationPath();
        
        return new Content(
            view: 'emails.invitation-image',
            with: [
                'guest' => $this->guest,
                'event' => $this->event,
                'invitationImageUrl' => asset('storage/' . $invitationPath),
                'emailMessage' => $settings['email_message'] ?? '',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->getInvitationPath())
                ->as('invitacion-'.$this->guest->numero_empleado.'.png')
                ->withMime('image/png'),
        ];
    }

    private function getInvitationPath(): string
    {
        if ($this->invitationPath === null) {
            $this->invitationPath = (new InvitationImageService())->generateInvitationWithQR($this->guest);
        }

        return $this->invitationPath;
    }
}
