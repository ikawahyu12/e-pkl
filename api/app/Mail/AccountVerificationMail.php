<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $verificationUrl,
        public readonly int $expiresInMinutes,
        public readonly string $roleLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifikasi Email Akun E-PKL');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-verification');
    }
}
