<?php

namespace App\Mail\Onboarding\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationCode;
    public string $type;

    /**
     * Create a new message instance.
     */
    public function __construct(string $verificationCode, string $type)
    {
        $this->verificationCode = $verificationCode;
        $this->type = ucfirst($type);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->type} Verification Code"
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Onboarding.emails.auth.verification-code',
            with: [
                'type' => $this->type,
                'verificationCode' => $this->verificationCode,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
