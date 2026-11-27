<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $clientId = config('services.google_mail.client_id');
        $clientSecret = config('services.google_mail.client_secret');
        $refreshToken = config('services.google_mail.refresh_token');

        if (
            ! is_string($clientId) || trim($clientId) === ''
            || ! is_string($clientSecret) || trim($clientSecret) === ''
            || ! is_string($refreshToken) || trim($refreshToken) === ''
        ) {
            throw new RuntimeException('Google Mail OAuth credentials are not configured.');
        }

        $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $accessToken = $tokenResponse->json('access_token');
        if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
            $error = $tokenResponse->json('error');
            $errorCode = is_string($error) ? $error : 'unknown_error';

            throw new RuntimeException(sprintf(
                'Google OAuth token refresh failed (HTTP %d, %s).',
                $tokenResponse->status(),
                $errorCode,
            ));
        }

        $rawMessage = $message->getOriginalMessage()->toString();
        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $sendResponse = Http::withToken($accessToken)
            ->timeout(20)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $encodedMessage,
            ]);

        if (! $sendResponse->successful()) {
            $errorStatus = $sendResponse->json('error.status');
            $errorCode = is_string($errorStatus) ? $errorStatus : 'unknown_error';

            throw new RuntimeException(sprintf(
                'Gmail API send failed (HTTP %d, %s).',
                $sendResponse->status(),
                $errorCode,
            ));
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
