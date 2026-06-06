<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Services\Newsletter\NewsletterDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendPostPublishedNewsletterEmail
{
    public function __construct(
        private readonly NewsletterDeliveryService $newsletterDeliveryService,
    ) {
    }

    public function handle(PostPublished $event): void
    {
        $sendTraceId = trim((string) ($event->sendTraceId ?? ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Post published newsletter listener started', [
            'send_trace_id' => $sendTraceId,
            'post_id' => $event->postId,
            'queue_connection' => config('queue.default'),
        ]);

        $post = Post::query()->find($event->postId);

        if (! $post) {
            return;
        }

        $slug = trim((string) $post->slug);
        if ($slug === '') {
            return;
        }

        $title = trim((string) $post->title);
        $summary = trim((string) ($post->description ?? ''));
        if ($summary === '') {
            $summary = 'Une nouvelle actualité a été publiée.';
        }

        $headline = $title !== '' ? "Nouvelle actualité : {$title}" : 'Nouvelle actualité disponible';
        $contentUrl = $this->publicBaseUrl() . '/actualites/' . $slug;

        NewsletterSubscriber::query()
            ->where('consent', true)
            ->whereNull('unsubscribed_at')
            ->where('is_suppressed', false)
            ->orderBy('id')
            ->select(['id', 'name', 'email', 'unsubscribe_token'])
            ->chunkById(100, function ($subscribers) use ($headline, $summary, $contentUrl, $sendTraceId): void {
                foreach ($subscribers as $subscriber) {
                    $email = trim((string) $subscriber->email);
                    $unsubscribeToken = trim((string) $subscriber->unsubscribe_token);

                    if ($email === '' || $unsubscribeToken === '') {
                        Log::warning('Newsletter recipient skipped due to missing identity data', [
                            'send_trace_id' => $sendTraceId,
                            'subscriber_id' => $subscriber->id,
                            'content_type' => 'post',
                            'content_url' => $contentUrl,
                        ]);

                        $this->paceSending();
                        continue;
                    }

                    try {
                        $result = $this->newsletterDeliveryService->sendToRecipient([
                            'recipient_email' => $email,
                            'recipient_name' => trim((string) $subscriber->name),
                            'headline' => $headline,
                            'summary' => $summary,
                            'content_url' => $contentUrl,
                            'content_type' => 'post',
                            'unsubscribe_url' => $this->unsubscribeUrl($unsubscribeToken),
                            'send_trace_id' => $sendTraceId,
                        ]);

                        if (! $result->success) {
                            $normalizedErrorText = $result->normalizedErrorText();
                            $this->suppressOnHardBounce($subscriber->id, $normalizedErrorText);

                            Log::warning('Newsletter content delivery failed', [
                                'send_trace_id' => $sendTraceId,
                                'subscriber_id' => $subscriber->id,
                                'recipient_email' => $email,
                                'content_type' => 'post',
                                'content_url' => $contentUrl,
                                'delivery_driver' => $this->newsletterDeliveryService->driver(),
                                'status' => $result->status,
                                'message_id' => $result->messageId,
                                'response_status' => $result->errorPayload['status'] ?? null,
                                'error' => $normalizedErrorText,
                                'error_payload' => $result->errorPayload,
                            ]);

                            $this->paceSending();
                            continue;
                        }

                        Log::info('Newsletter content delivery succeeded', [
                            'send_trace_id' => $sendTraceId,
                            'subscriber_id' => $subscriber->id,
                            'recipient_email' => $email,
                            'content_type' => 'post',
                            'content_url' => $contentUrl,
                            'delivery_driver' => $this->newsletterDeliveryService->driver(),
                            'status' => $result->status,
                            'message_id' => $result->messageId,
                            'brevo_message_id' => $result->messageId,
                        ]);
                    } catch (\Throwable $exception) {
                        $this->suppressOnHardBounce($subscriber->id, $exception->getMessage());

                        Log::warning('Newsletter content delivery failed', [
                            'send_trace_id' => $sendTraceId,
                            'subscriber_id' => $subscriber->id,
                            'recipient_email' => $email,
                            'content_type' => 'post',
                            'content_url' => $contentUrl,
                            'delivery_driver' => $this->newsletterDeliveryService->driver(),
                            'status' => 'failed',
                            'message_id' => null,
                            'response_status' => null,
                            'error' => $exception->getMessage(),
                            'error_payload' => [],
                        ]);
                    }

                    $this->paceSending();
                }
            });
    }

    private function publicBaseUrl(): string
    {
        $baseUrl = (string) config('services.newsletter.public_base_url', 'https://www.nordsudaction.ma');

        return rtrim($baseUrl, '/');
    }

    private function unsubscribeUrl(string $token): string
    {
        $baseUrl = (string) config('services.newsletter.unsubscribe_base_url', config('app.url', 'http://localhost'));

        return rtrim($baseUrl, '/') . '/unsubscribe/' . $token;
    }

    private function paceSending(): void
    {
        $delaySeconds = max(0, (int) config('services.newsletter.send_delay_seconds', 0));

        if ($delaySeconds > 0) {
            sleep($delaySeconds);
        }
    }

    private function suppressOnHardBounce(int $subscriberId, string $errorMessage): void
    {
        if (! $this->isHardBounceForRecipient($errorMessage)) {
            return;
        }

        NewsletterSubscriber::query()
            ->where('id', $subscriberId)
            ->update([
                'is_suppressed' => true,
                'suppressed_at' => now(),
                'suppression_reason' => mb_substr($errorMessage, 0, 255),
            ]);

        Log::warning('Newsletter subscriber auto-suppressed after hard bounce', [
            'subscriber_id' => $subscriberId,
            'error' => $errorMessage,
        ]);
    }

    private function isHardBounceForRecipient(string $errorMessage): bool
    {
        $normalized = mb_strtolower($errorMessage);

        $recipientHardBounceMarkers = [
            '5.1.1',
            'user unknown',
            'no such user',
            'unknown recipient',
            'invalid recipient',
            'recipient address rejected',
            'invalid email',
            'email address is not valid',
        ];

        foreach ($recipientHardBounceMarkers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }
}
