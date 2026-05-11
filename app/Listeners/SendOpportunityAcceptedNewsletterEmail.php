<?php

namespace App\Listeners;

use App\Events\OpportunityAccepted;
use App\Mail\NewsletterContentPublishedMail;
use App\Models\NewsletterSubscriber;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOpportunityAcceptedNewsletterEmail
{

    public function handle(OpportunityAccepted $event): void
    {
        $opportunity = Opportunity::query()->find($event->opportunityId);

        if (! $opportunity) {
            return;
        }

        $title = trim((string) $opportunity->titre);
        $summary = trim((string) ($opportunity->description ?? ''));
        if ($summary === '') {
            $summary = 'Une nouvelle opportunité est disponible.';
        }

        $headline = $title !== '' ? "Nouvelle opportunité: {$title}" : 'Nouvelle opportunité disponible';
        $contentUrl = $this->publicBaseUrl() . '/opportunities/' . $opportunity->id;

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
                            contentType: 'opportunity',
                        ));
                    } catch (\Throwable $exception) {
                        Log::warning('Newsletter content mail send failed', [
                            'recipient_email' => $email,
                            'content_type' => 'opportunity',
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
