<?php

namespace App\Listeners;

use App\Events\OpportunitySubmitted;
use App\Models\Opportunity;
use App\Services\Email\EmailDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendOpportunitySubmissionEmail
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

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

            $body = "Nouvelle opportunité soumise\n\n"
                . "Titre : {$opportunity->titre}\n"
                . "Nom : {$opportunity->first_name} {$opportunity->last_name}\n"
                . "E-mail : {$opportunity->email}\n"
                . "Téléphone : {$opportunity->phone}\n"
                . "Ville : {$opportunity->ville}\n"
                . "Type : " . ($opportunity->type?->name ?? '-') . "\n"
                . "Budget : " . ($opportunity->budget ? number_format($opportunity->budget, 2, ',', ' ') . ' MAD' : '-') . "\n"
                . "Nombre d'images : {$imageLinks->count()}\n\n"
                . "Description:\n{$opportunity->description}";

            if ($imageLinks->isNotEmpty()) {
                $body .= "\n\nLiens des images :\n- " . $imageLinks->implode("\n- ");
            }

            $result = $this->emailDeliveryService->send([
                'to_email' => $recipient,
                'subject' => "Nouvelle opportunité soumise : {$opportunity->titre}",
                'text_content' => $body,
                'reply_to_email' => $opportunity->email,
                'reply_to_name' => trim($opportunity->first_name . ' ' . $opportunity->last_name),
                'tags' => ['opportunity-submission'],
            ]);

            if (! $result->success) {
                Log::warning('Opportunity notification mail failed', [
                    'opportunity_id' => $event->opportunityId,
                    'recipient' => $recipient,
                    'status' => $result->status,
                    'message_id' => $result->messageId,
                    'response_status' => $result->errorPayload['status'] ?? null,
                    'error' => $result->normalizedErrorText(),
                    'error_payload' => $result->errorPayload,
                ]);

                return;
            }

            Log::info('Opportunity notification mail delivery succeeded', [
                'opportunity_id' => $event->opportunityId,
                'recipient' => $recipient,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'brevo_message_id' => $result->messageId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Opportunity notification mail failed', [
                'opportunity_id' => $event->opportunityId,
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
