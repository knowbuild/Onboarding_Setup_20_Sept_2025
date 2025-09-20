<?php

namespace App\Mail\Onboarding\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable 
{
    use Queueable, SerializesModels;

    public $user;
    public $setupPasswordUrl;
    /**
     * Create a new message instance.
     */
    public function __construct($user, string $setupPasswordUrl)
    {
        $this->user = $user;
        $this->setupPasswordUrl = $setupPasswordUrl;
    }

    /**
     * Define the envelope for the email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Knowbuild :: Setup New Password'
        );
    }

    /**
     * Define the content for the email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Onboarding.emails.auth.welcome',
            with: [
                'name' => $this->user->admin_fname,
                'setupPasswordUrl' => $this->setupPasswordUrl
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
