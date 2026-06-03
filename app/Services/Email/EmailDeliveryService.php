<?php

namespace App\Services\Email;

class EmailDeliveryService
{
    public function __construct(
        private readonly BrevoApiClient $brevoApiClient,
    ) {
    }

    /**
     * @param array{
     *   to_email:string,
     *   to_name?:string,
     *   subject:string,
     *   text_content?:string,
     *   html_content?:string,
     *   reply_to_email?:string,
     *   reply_to_name?:string,
     *   tags?:array<int,string>,
     *   headers?:array<string,string>,
    *   attachments?:array<int,array{name:string,content:string}>
     * } $message
     */
    public function send(array $message): EmailDeliveryResult
    {
        $senderEmail = trim((string) config('mail.from.address', ''));
        $senderName = trim((string) config('mail.from.name', ''));

        if ($senderEmail === '') {
            return EmailDeliveryResult::failed('MAIL_FROM_ADDRESS is missing, cannot send email via Brevo API.');
        }

        $payload = [
            'sender' => [
                'email' => $senderEmail,
                'name' => $senderName !== '' ? $senderName : null,
            ],
            'to' => [[
                'email' => $message['to_email'],
                'name' => trim((string) ($message['to_name'] ?? '')),
            ]],
            'subject' => $message['subject'],
        ];

        if ($payload['sender']['name'] === null) {
            unset($payload['sender']['name']);
        }

        if ($payload['to'][0]['name'] === '') {
            unset($payload['to'][0]['name']);
        }

        $textContent = trim((string) ($message['text_content'] ?? ''));
        $htmlContent = (string) ($message['html_content'] ?? '');

        if ($textContent === '' && trim($htmlContent) === '') {
            return EmailDeliveryResult::failed('Either text_content or html_content must be provided.');
        }

        if ($textContent !== '') {
            $payload['textContent'] = $textContent;
        }

        if (trim($htmlContent) !== '') {
            $payload['htmlContent'] = $htmlContent;
        }

        $replyToEmail = trim((string) ($message['reply_to_email'] ?? ''));
        if ($replyToEmail !== '') {
            $replyTo = ['email' => $replyToEmail];
            $replyToName = trim((string) ($message['reply_to_name'] ?? ''));
            if ($replyToName !== '') {
                $replyTo['name'] = $replyToName;
            }
            $payload['replyTo'] = $replyTo;
        }

        if (! empty($message['tags']) && is_array($message['tags'])) {
            $payload['tags'] = array_values(array_filter(array_map('strval', $message['tags'])));
        }

        if (! empty($message['headers']) && is_array($message['headers'])) {
            $headers = [];
            foreach ($message['headers'] as $key => $value) {
                $headerName = trim((string) $key);
                $headerValue = trim((string) $value);
                if ($headerName !== '' && $headerValue !== '') {
                    $headers[$headerName] = $headerValue;
                }
            }
            if (! empty($headers)) {
                $payload['headers'] = $headers;
            }
        }

        if (! empty($message['attachments']) && is_array($message['attachments'])) {
            $attachments = [];
            foreach ($message['attachments'] as $attachment) {
                $name = trim((string) ($attachment['name'] ?? ''));
                $content = trim((string) ($attachment['content'] ?? ''));
                if ($name === '' || $content === '') {
                    continue;
                }

                $normalizedAttachment = [
                    'name' => $name,
                    'content' => $content,
                ];

                $attachments[] = $normalizedAttachment;
            }

            if (! empty($attachments)) {
                $payload['attachment'] = $attachments;
            }
        }

        return $this->brevoApiClient->sendTransactionalEmail($payload);
    }

    public function provider(): string
    {
        return 'brevo_api';
    }
}
