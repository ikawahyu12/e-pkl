<?php

namespace Tests\Feature;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GmailApiMailTransportTest extends TestCase
{
    public function test_it_sends_a_mime_message_through_the_gmail_api(): void
    {
        config([
            'services.google_mail.client_id' => 'client-id',
            'services.google_mail.client_secret' => 'client-secret',
            'services.google_mail.refresh_token' => 'refresh-token',
            'mail.mailers.gmail-api.transport' => 'gmail-api',
            'mail.from.address' => 'kelolaepkl@gmail.com',
            'mail.from.name' => 'E-PKL',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'message-id',
            ]),
        ]);

        Mail::mailer('gmail-api')->raw('Email body for transport test.', function ($message): void {
            $message->to('student@example.test')->subject('Test Gmail API');
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send') {
                return false;
            }

            $encoded = $request->data()['raw'] ?? '';
            $decoded = base64_decode(strtr((string) $encoded, '-_', '+/'));

            return $request->hasHeader('Authorization', 'Bearer access-token')
                && is_string($decoded)
                && str_contains($decoded, 'Subject: Test Gmail API')
                && str_contains($decoded, 'Email body for transport test.');
        });
    }

    public function test_it_rejects_missing_oauth_credentials_without_making_a_request(): void
    {
        config([
            'services.google_mail.client_id' => '',
            'services.google_mail.client_secret' => '',
            'services.google_mail.refresh_token' => '',
        ]);

        Http::fake();

        $message = (new Email)
            ->from('kelolaepkl@gmail.com')
            ->to('student@example.test')
            ->subject('Test Gmail API')
            ->text('Email body.');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Mail OAuth credentials are not configured.');

        try {
            (new GmailApiTransport)->send($message);
        } finally {
            Http::assertNothingSent();
        }
    }
}
