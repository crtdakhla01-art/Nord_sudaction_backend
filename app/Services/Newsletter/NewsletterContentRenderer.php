<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\View;

class NewsletterContentRenderer
{
    public function renderContentPublishedEmail(
        string $recipientName,
        string $headline,
        string $summary,
        string $contentUrl,
        string $contentType,
        string $unsubscribeUrl,
    ): string {
        return View::make('emails.newsletter-content-published', [
            'recipientName' => $recipientName,
            'headline' => $headline,
            'summary' => $summary,
            'contentUrl' => $contentUrl,
            'contentType' => $contentType,
            'unsubscribeUrl' => $unsubscribeUrl,
        ])->render();
    }
}
