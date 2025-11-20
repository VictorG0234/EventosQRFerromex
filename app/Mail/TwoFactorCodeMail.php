<?php

namespace App\Mail;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $user;
    public $expiresIn;

    /**
     * Create a new message instance.
     */
    public function __construct(TwoFactorCode $twoFactorCode)
    {
        $this->code = $twoFactorCode->code;
        $this->user = $twoFactorCode->user;
        $this->expiresIn = now()->diffInMinutes($twoFactorCode->expires_at);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificación - Ferromex Eventos',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.two-factor-code',
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
