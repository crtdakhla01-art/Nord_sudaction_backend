<?php

namespace App\Listeners;

use App\Events\InscriptionSubmitted;
use App\Models\Inscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            $paymentProofPublicUrl = $inscription->payment_proof_path
                ? Storage::disk('public')->url($inscription->payment_proof_path)
                : null;
            $cinCopyPublicUrl = $inscription->cin_copy_path
                ? Storage::disk('public')->url($inscription->cin_copy_path)
                : null;

            $body = "Nouvelle inscription reçue\n\n"
                . "Nom complet : {$inscription->full_name}\n"
                . "E-mail : {$inscription->email}\n"
                . "Téléphone : {$inscription->phone}\n"
                . "Ville : {$inscription->city}\n"
                . "Date de naissance : " . ($inscription->birth_date?->format('Y-m-d') ?? '-') . "\n"
                . "Profession : " . ($inscription->profession ?: '-') . "\n"
                . "Organisation : " . ($inscription->organization ?: '-') . "\n"
                . "Profils du participant : " . ($participantProfiles->isNotEmpty() ? $participantProfiles->implode(', ') : '-') . "\n"
                . "Autre profil : " . ($inscription->participant_profile_other ?: '-') . "\n"
                . "Secteurs d'investissement : " . ($investmentSectors->isNotEmpty() ? $investmentSectors->implode(', ') : '-') . "\n"
                . "Autre secteur : " . ($inscription->investment_sector_other ?: '-') . "\n"
                . "Activités confirmées : " . ($confirmedActivities->isNotEmpty() ? $confirmedActivities->implode(', ') : '-') . "\n"
                . "Frais de participation : " . number_format((float) $inscription->participation_fee, 2, ',', ' ') . " MAD\n"
                . "Conditions acceptées : " . ($inscription->is_terms_accepted ? 'oui' : 'non') . "\n"
                . "Paiement effectué : " . ($inscription->is_paid ? 'oui' : 'non') . "\n"
                . "Date de paiement : " . ($inscription->paid_at?->format('Y-m-d H:i:s') ?? '-') . "\n"
                . "Justificatif de paiement (URL) : " . ($paymentProofPublicUrl ?: '-') . "\n"
                . "Copie CIN (URL): " . ($cinCopyPublicUrl ?: '-');

            Mail::raw($body, function ($message) use ($inscription, $recipient): void {
                $message->to($recipient)
                    ->replyTo($inscription->email, $inscription->full_name)
                    ->subject("Nouvelle inscription soumise : {$inscription->full_name}");

                if ($inscription->payment_proof_path && Storage::disk('public')->exists($inscription->payment_proof_path)) {
                    $message->attach(
                        Storage::disk('public')->path($inscription->payment_proof_path),
                        ['as' => basename($inscription->payment_proof_path)]
                    );
                }

                if ($inscription->cin_copy_path && Storage::disk('public')->exists($inscription->cin_copy_path)) {
                    $message->attach(
                        Storage::disk('public')->path($inscription->cin_copy_path),
                        ['as' => basename($inscription->cin_copy_path)]
                    );
                }
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
