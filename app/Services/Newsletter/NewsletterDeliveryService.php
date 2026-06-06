<?php

namespace App\Services\Newsletter;

use App\Services\Email\EmailDeliveryResult;
use App\Services\Email\EmailDeliveryService;

class NewsletterDeliveryService
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
        private readonly NewsletterContentRenderer $contentRenderer,
    ) {
    }

    /**
     * @param array{recipient_email:string,recipient_name:string,headline:string,summary:string,content_url:string,content_type:string,unsubscribe_url:string,send_trace_id?:string} $message
     */
    public function sendToRecipient(array $message): EmailDeliveryResult
    {
        return $this->sendViaBrevoApi($message);
    }

    public function driver(): string
    {
        return $this->emailDeliveryService->provider();
    }

    public function batchEnabled(): bool
    {
        return (bool) config('services.newsletter.brevo.use_batch', false);
    }

    public function batchSize(): int
    {
        return max(1, (int) config('services.newsletter.brevo.batch_size', 100));
    }

    /**
     * Placeholder for phase 2 batch mode support.
     *
     * @param array<int, array{recipient_email:string,recipient_name:string,headline:string,summary:string,content_url:string,content_type:string,unsubscribe_url:string,send_trace_id?:string}> $messages
     * @return array<int, EmailDeliveryResult>
     */
    public function sendBatch(array $messages): array
    {
        return array_map(fn (array $message) => $this->sendToRecipient($message), $messages);
    }

    /**
     * @param array{recipient_email:string,recipient_name:string,headline:string,summary:string,content_url:string,content_type:string,unsubscribe_url:string,send_trace_id?:string} $message
     */
    private function sendViaBrevoApi(array $message): EmailDeliveryResult
    {
        $senderEmail = trim((string) config('mail.from.address', ''));

        $unsubscribeMailto = trim((string) config('services.newsletter.unsubscribe_mailto', $senderEmail));
        $listUnsubscribeValue = $unsubscribeMailto !== ''
            ? "<mailto:{$unsubscribeMailto}?subject=unsubscribe>, <{$message['unsubscribe_url']}>"
            : "<{$message['unsubscribe_url']}>";

        $htmlContent = $this->contentRenderer->renderContentPublishedEmail(
            recipientName: $message['recipient_name'],
            headline: $message['headline'],
            summary: $message['summary'],
            contentUrl: $message['content_url'],
            contentType: $message['content_type'],
            unsubscribeUrl: $message['unsubscribe_url'],
        );

        $headers = [];

        if ((bool) config('services.newsletter.brevo.enable_list_unsubscribe_headers', false)) {
            $headers = [
                'List-Unsubscribe' => $listUnsubscribeValue,
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ];
        }

        $sendTraceId = trim((string) ($message['send_trace_id'] ?? ''));
        if ($sendTraceId !== '') {
            $headers['X-Send-Trace-Id'] = $sendTraceId;
        }

        return $this->emailDeliveryService->send([
            'to_email' => $message['recipient_email'],
            'to_name' => $message['recipient_name'],
            'subject' => $message['headline'],
            'html_content' => $htmlContent,
            'headers' => $headers,
            'tags' => ['newsletter'],
        ]);
    }
}
