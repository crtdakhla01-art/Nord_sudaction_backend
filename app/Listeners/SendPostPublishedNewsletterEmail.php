<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Mail\NewsletterContentPublishedMail;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPostPublishedNewsletterEmail
{

    public function handle(PostPublished $event): void
    {
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
            ->orderBy('id')
            ->select(['id', 'name', 'email'])
            ->chunkById(100, function ($subscribers) use ($headline, $summary, $contentUrl): void {
                foreach ($subscribers as $subscriber) {
                    $email = trim((string) $subscriber->email);
                    if ($email === '') {
                        continue;
                    }

                    try {
                        Mail::to($email)->send(new NewsletterContentPublishedMail(
                            recipientName: trim((string) $subscriber->name),
                            headline: $headline,
                            summary: $summary,
                            contentUrl: $contentUrl,
                            contentType: 'post',
                        ));
                    } catch (\Throwable $exception) {
                        Log::warning('Newsletter content mail send failed', [
                            'recipient_email' => $email,
                            'content_type' => 'post',
                            'content_url' => $contentUrl,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });
    }

    private function publicBaseUrl(): string
    {
        $baseUrl = (string) config('services.newsletter.public_base_url', 'https://www.nordsudaction.ma');

        return rtrim($baseUrl, '/');
    }
}
