<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class NewsletterContentPublishedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $headline,
        public readonly string $summary,
        public readonly string $contentUrl,
        public readonly string $contentType,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->headline)
            ->view('emails.newsletter-content-published');
    }
}
