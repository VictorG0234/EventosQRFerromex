<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventSummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Event $event
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resumen del Evento: ' . $this->event->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Calculamos estadísticas del evento
        $totalGuests = $this->event->guests()->count();
        $attendedGuests = $this->event->attendances()->count();
        $attendanceRate = $totalGuests > 0 ? round(($attendedGuests / $totalGuests) * 100, 2) : 0;
        
        return new Content(
            view: 'emails.event-summary',
            with: [
                'event' => $this->event,
                'organizer' => $this->event->user,
                'statistics' => [
                    'total_guests' => $totalGuests,
                    'attended_guests' => $attendedGuests,
                    'attendance_rate' => $attendanceRate,
                    'pending_guests' => $totalGuests - $attendedGuests
                ]
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