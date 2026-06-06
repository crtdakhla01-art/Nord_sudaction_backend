<?php

namespace App\Listeners;

use App\Events\InscriptionSubmitted;
use App\Models\Inscription;
use App\Services\Email\EmailDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendInscriptionSubmissionEmail
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    public function handle(InscriptionSubmitted $event): void
    {
        $sendTraceId = trim((string) ($event->sendTraceId ?? ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Inscription listener started', [
            'send_trace_id' => $sendTraceId,
            'inscription_id' => $event->inscriptionId,
            'queue_connection' => config('queue.default'),
        ]);

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

            $attachments = [];

            if ($inscription->payment_proof_path && Storage::disk('public')->exists($inscription->payment_proof_path)) {
                $attachments[] = [
                    'name' => basename($inscription->payment_proof_path),
                    'content' => base64_encode(Storage::disk('public')->get($inscription->payment_proof_path)),
                ];
            }

            if ($inscription->cin_copy_path && Storage::disk('public')->exists($inscription->cin_copy_path)) {
                $attachments[] = [
                    'name' => basename($inscription->cin_copy_path),
                    'content' => base64_encode(Storage::disk('public')->get($inscription->cin_copy_path)),
                ];
            }

            $result = $this->emailDeliveryService->send([
                'to_email' => $recipient,
                'subject' => "Nouvelle inscription soumise : {$inscription->full_name}",
                'text_content' => $body,
                'reply_to_email' => $inscription->email,
                'reply_to_name' => $inscription->full_name,
                'attachments' => $attachments,
                'tags' => ['inscription-submission'],
                'headers' => [
                    'X-Send-Trace-Id' => $sendTraceId,
                ],
            ]);

            if (! $result->success) {
                Log::warning('Inscription notification mail failed', [
                    'send_trace_id' => $sendTraceId,
                    'inscription_id' => $event->inscriptionId,
                    'recipient' => $recipient,
                    'status' => $result->status,
                    'message_id' => $result->messageId,
                    'response_status' => $result->errorPayload['status'] ?? null,
                    'error' => $result->normalizedErrorText(),
                    'error_payload' => $result->errorPayload,
                ]);

                return;
            }

            Log::info('Inscription notification mail delivery succeeded', [
                'send_trace_id' => $sendTraceId,
                'inscription_id' => $event->inscriptionId,
                'recipient' => $recipient,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'brevo_message_id' => $result->messageId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Inscription notification mail failed', [
                'send_trace_id' => $sendTraceId,
                'inscription_id' => $event->inscriptionId,
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
