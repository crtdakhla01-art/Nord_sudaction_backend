<?php

namespace App\Jobs;

use App\Models\Opportunity;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendOpportunityNotificationMail
{
    use Dispatchable;

    public function __construct(private readonly int $opportunityId)
    {
    }

    public function handle(): void
    {
        $opportunity = Opportunity::query()
            ->with(['type:id,name', 'images:id,opportunity_id,path,sort_order'])
            ->find($this->opportunityId);

        if (! $opportunity) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $imageLinks = $opportunity->images
                ->map(fn ($image) => Storage::disk('public')->url($image->path))
                ->values();

            $body = "Nouvelle opportunité soumise\n\n"
                . "Titre: {$opportunity->titre}\n"
                . "Nom: {$opportunity->first_name} {$opportunity->last_name}\n"
                . "Email: {$opportunity->email}\n"
                . "Téléphone: {$opportunity->phone}\n"
                . "Ville: {$opportunity->ville}\n"
                . "Type: " . ($opportunity->type?->name ?? '-') . "\n"
                . "Budget: " . ($opportunity->budget ? number_format($opportunity->budget, 2, ',', ' ') . ' MAD' : '-') . "\n"
                . "Nombre d'images: {$imageLinks->count()}\n\n"
                . "Description:\n{$opportunity->description}";

            if ($imageLinks->isNotEmpty()) {
                $body .= "\n\nLiens des images:\n- " . $imageLinks->implode("\n- ");
            }

            Mail::raw($body, function ($message) use ($opportunity, $recipient) {
                $message->to($recipient)
                    ->replyTo($opportunity->email, $opportunity->first_name . ' ' . $opportunity->last_name)
                    ->subject("Nouvelle opportunité soumise : {$opportunity->titre}");
            });
        } catch (\Throwable $exception) {
            Log::warning('Opportunity notification mail failed', [
                'opportunity_id' => $this->opportunityId,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
