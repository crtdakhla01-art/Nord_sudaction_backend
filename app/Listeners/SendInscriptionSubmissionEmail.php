<?php

namespace App\Listeners;

use App\Events\InscriptionSubmitted;
use App\Models\Inscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInscriptionSubmissionEmail
{
    public function handle(InscriptionSubmitted $event): void
    {
        $inscription = Inscription::query()->find($event->inscriptionId);

        if (! $inscription) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $participantProfiles = collect($inscription->participant_profiles ?? [])->filter()->values();
            $investmentSectors = collect($inscription->investment_sectors ?? [])->filter()->values();
            $confirmedActivities = collect($inscription->confirmed_activities ?? [])->filter()->values();

            $body = "Nouvelle inscription recue\n\n"
                . "Nom complet: {$inscription->full_name}\n"
                . "Email: {$inscription->email}\n"
                . "Telephone: {$inscription->phone}\n"
                . "Ville: {$inscription->city}\n"
                . "Date de naissance: " . ($inscription->birth_date?->format('Y-m-d') ?? '-') . "\n"
                . "Profession: " . ($inscription->profession ?: '-') . "\n"
                . "Organisation: " . ($inscription->organization ?: '-') . "\n"
                . "Profils participant: " . ($participantProfiles->isNotEmpty() ? $participantProfiles->implode(', ') : '-') . "\n"
                . "Autre profil: " . ($inscription->participant_profile_other ?: '-') . "\n"
                . "Secteurs investissement: " . ($investmentSectors->isNotEmpty() ? $investmentSectors->implode(', ') : '-') . "\n"
                . "Autre secteur: " . ($inscription->investment_sector_other ?: '-') . "\n"
                . "Activites confirmees: " . ($confirmedActivities->isNotEmpty() ? $confirmedActivities->implode(', ') : '-') . "\n"
                . "Frais participation: " . number_format((float) $inscription->participation_fee, 2, ',', ' ') . " MAD\n"
                . "Informations confirmees: " . ($inscription->is_information_confirmed ? 'oui' : 'non') . "\n"
                . "Conditions acceptees: " . ($inscription->is_terms_accepted ? 'oui' : 'non') . "\n"
                . "Paiement effectue: " . ($inscription->is_paid ? 'oui' : 'non') . "\n"
                . "Date de paiement: " . ($inscription->paid_at?->format('Y-m-d H:i:s') ?? '-');

            Mail::raw($body, function ($message) use ($inscription, $recipient): void {
                $message->to($recipient)
                    ->replyTo($inscription->email, $inscription->full_name)
                    ->subject("Nouvelle inscription soumise : {$inscription->full_name}");
            });
        } catch (\Throwable $exception) {
            Log::warning('Inscription notification mail failed', [
                'inscription_id' => $event->inscriptionId,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
