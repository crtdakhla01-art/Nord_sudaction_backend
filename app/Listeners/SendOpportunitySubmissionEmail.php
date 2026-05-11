<?php

namespace App\Listeners;

use App\Events\OpportunitySubmitted;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendOpportunitySubmissionEmail
{

    public function handle(OpportunitySubmitted $event): void
    {
        $opportunity = Opportunity::query()
            ->with(['type:id,name', 'images:id,opportunity_id,path,sort_order'])
            ->find($event->opportunityId);

        if (! $opportunity) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $imageLinks = $opportunity->images
                ->map(fn ($image) => Storage::disk('public')->url($image->path))
                ->values();

            $body = "Nouvelle opportunite soumise\n\n"
                . "Titre: {$opportunity->titre}\n"
                . "Nom: {$opportunity->first_name} {$opportunity->last_name}\n"
                . "Email: {$opportunity->email}\n"
                . "Telephone: {$opportunity->phone}\n"
                . "Ville: {$opportunity->ville}\n"
                . "Type: " . ($opportunity->type?->name ?? '-') . "\n"
                . "Budget: " . ($opportunity->budget ? number_format($opportunity->budget, 2, ',', ' ') . ' MAD' : '-') . "\n"
                . "Nombre d'images: {$imageLinks->count()}\n\n"
                . "Description:\n{$opportunity->description}";

            if ($imageLinks->isNotEmpty()) {
                $body .= "\n\nLiens des images:\n- " . $imageLinks->implode("\n- ");
            }

            Mail::raw($body, function ($message) use ($opportunity, $recipient): void {
                $message->to($recipient)
                    ->replyTo($opportunity->email, $opportunity->first_name . ' ' . $opportunity->last_name)
                    ->subject("Nouvelle opportunité soumise : {$opportunity->titre}");
            });
        } catch (\Throwable $exception) {
            Log::warning('Opportunity notification mail failed', [
                'opportunity_id' => $event->opportunityId,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
