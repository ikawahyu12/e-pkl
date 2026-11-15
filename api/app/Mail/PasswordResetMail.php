<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $resetUrl,
        public readonly int $expiresInMinutes,
        public readonly ?string $browserFallbackUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tautan Reset Kata Sandi E-PKL');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset');
    }
}
