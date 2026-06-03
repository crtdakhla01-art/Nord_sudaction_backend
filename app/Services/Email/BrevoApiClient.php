<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoApiClient
{
    public function sendTransactionalEmail(array $payload): EmailDeliveryResult
    {
        $apiKey = trim((string) config('services.email.brevo.api_key', ''));

        Log::debug('BrevoApiClient request start', [
            'has_api_key' => $apiKey !== '',
            'to' => $payload['to'] ?? [],
            'subject' => $payload['subject'] ?? null,
            'tags' => $payload['tags'] ?? [],
        ]);

        if ($apiKey === '') {
            return EmailDeliveryResult::failed('Brevo API key is missing.');
        }

        $baseUrl = rtrim((string) config('services.email.brevo.api_base_url', 'https://api.brevo.com'), '/');
        $timeout = max(1, (int) config('services.email.brevo.timeout', 15));
        $connectTimeout = max(1, (int) config('services.email.brevo.connect_timeout', 5));

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'api-key' => $apiKey,
                ])
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->post($baseUrl . '/v3/smtp/email', $payload);
        } catch (\Throwable $exception) {
            Log::warning('BrevoApiClient request failed with exception', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return EmailDeliveryResult::failed(
                errorMessage: $exception->getMessage(),
                errorPayload: [
                    'status' => null,
                    'body' => null,
                    'exception' => $exception::class,
                ],
            );
        }

        Log::debug('BrevoApiClient response received', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->successful()) {
            $messageId = $response->json('messageId');

            if ($messageId === null) {
                $messageIds = $response->json('messageIds');
                if (is_array($messageIds) && isset($messageIds[0]) && is_string($messageIds[0])) {
                    $messageId = $messageIds[0];
                }
            }

            Log::debug('BrevoApiClient messageId extracted', [
                'message_id' => is_string($messageId) ? $messageId : null,
            ]);

            return EmailDeliveryResult::submitted(is_string($messageId) ? $messageId : null);
        }

        $errorPayload = $response->json();
        if (! is_array($errorPayload)) {
            $errorPayload = [
                'body' => $response->body(),
                'status' => $response->status(),
            ];
        } else {
            $errorPayload['status'] = $response->status();
        }

        $errorMessage = (string) ($errorPayload['message'] ?? $errorPayload['body'] ?? 'Brevo transactional API request failed.');

        Log::warning('BrevoApiClient failure payload', [
            'status' => $response->status(),
            'error_payload' => $errorPayload,
        ]);

        return EmailDeliveryResult::failed($errorMessage, $errorPayload);
    }
}
