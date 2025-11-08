<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Guest $guest,
        public int $hoursBeforeEvent
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->hoursBeforeEvent <= 2 
            ? '¡Tu evento comienza pronto! - ' . $this->guest->event->name
            : 'Recordatorio: ' . $this->guest->event->name . ' en ' . $this->hoursBeforeEvent . ' horas';
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.event-reminder',
            with: [
                'guest' => $this->guest,
                'event' => $this->guest->event,
                'hoursBeforeEvent' => $this->hoursBeforeEvent,
                'qrCodeUrl' => $this->guest->qr_code_url
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}