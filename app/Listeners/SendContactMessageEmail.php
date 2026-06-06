<?php

namespace App\Listeners;

use App\Events\ContactMessageCreated;
use App\Models\ContactMessage;
use App\Services\Email\EmailDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendContactMessageEmail
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    public function handle(ContactMessageCreated $event): void
    {
        $sendTraceId = trim((string) ($event->sendTraceId ?? ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Contact listener started', [
            'send_trace_id' => $sendTraceId,
            'contact_message_id' => $event->contactMessageId,
            'queue_connection' => config('queue.default'),
        ]);

        $contactMessage = ContactMessage::query()->find($event->contactMessageId);

        if (! $contactMessage) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $body = "Nouveau message de contact\n\nNom : {$contactMessage->name}\nE-mail : {$contactMessage->email}\nTéléphone : " . ($contactMessage->phone ?: '-') . "\nObjet : {$contactMessage->object}\n\nMessage :\n{$contactMessage->message}";

            $result = $this->emailDeliveryService->send([
                'to_email' => $recipient,
                'subject' => "Nouveau message de contact : {$contactMessage->object}",
                'text_content' => $body,
                'reply_to_email' => $contactMessage->email,
                'reply_to_name' => $contactMessage->name,
                'tags' => ['contact-message'],
                'headers' => [
                    'X-Send-Trace-Id' => $sendTraceId,
                ],
            ]);

            if (! $result->success) {
                Log::warning('Contact message mail send failed', [
                    'send_trace_id' => $sendTraceId,
                    'contact_message_id' => $event->contactMessageId,
                    'sender_email' => $contactMessage->email,
                    'recipient' => $recipient,
                    'status' => $result->status,
                    'message_id' => $result->messageId,
                    'response_status' => $result->errorPayload['status'] ?? null,
                    'error' => $result->normalizedErrorText(),
                    'error_payload' => $result->errorPayload,
                ]);

                return;
            }

            Log::info('Contact message mail delivery succeeded', [
                'send_trace_id' => $sendTraceId,
                'contact_message_id' => $event->contactMessageId,
                'recipient' => $recipient,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'brevo_message_id' => $result->messageId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Contact message mail send failed', [
                'send_trace_id' => $sendTraceId,
                'contact_message_id' => $event->contactMessageId,
                'sender_email' => $contactMessage->email,
                'recipient' => $recipient,
                'status' => 'failed',
                'message_id' => null,
                'response_status' => null,
                'error' => $exception->getMessage(),
                'error_payload' => [],
            ]);
        }
    }
}
