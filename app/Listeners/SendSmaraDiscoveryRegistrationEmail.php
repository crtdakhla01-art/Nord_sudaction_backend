<?php

namespace App\Listeners;

use App\Events\SmaraDiscoveryRegistrationSubmitted;
use App\Models\SmaraDiscoveryRegistration;
use App\Services\Email\EmailDeliveryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendSmaraDiscoveryRegistrationEmail
{
    private const AGE_GROUP_LABELS = [
        'under_25' => 'Moins de 25 ans',
        '25_34' => '25 – 34 ans',
        '35_44' => '35 – 44 ans',
        '45_54' => '45 – 54 ans',
        '55_plus' => '55 ans et plus',
    ];

    private const INTEREST_LEVEL_LABELS = [
        'certainly' => 'Oui, certainement',
        'probably' => 'Probablement',
        'maybe' => 'Peut-être',
    ];

    private const PARTICIPANTS_COUNT_LABELS = [
        '1' => '1',
        '2' => '2',
        '3_or_more' => '3 ou plus',
    ];

    private const PREFERRED_DURATION_LABELS = [
        'weekend' => 'Week-end (2 jours / 1 nuit)',
        '3_days' => '3 jours / 2 nuits',
        '4_days_plus' => '4 jours ou plus',
    ];

    private const ACTIVITY_LABELS = [
        'astrotourism' => 'Astrotourisme',
        'bivouac' => 'Bivouac',
        'hiking' => 'Randonnée',
        'archaeological_sites' => 'Sites archéologiques',
        'hassani_culture' => 'Culture hassanie',
        'wildlife_observation' => 'Observation de la faune et de la flore',
        'photography' => 'Photographie',
    ];

    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    public function handle(SmaraDiscoveryRegistrationSubmitted $event): void
    {
        $sendTraceId = trim((string) ($event->sendTraceId ?? ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Smara discovery registration listener started', [
            'send_trace_id' => $sendTraceId,
            'registration_id' => $event->registrationId,
            'queue_connection' => config('queue.default'),
        ]);

        $registration = SmaraDiscoveryRegistration::query()->find($event->registrationId);

        if (! $registration) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            $activities = collect($registration->preferred_activities ?? [])
                ->map(fn ($activity) => self::ACTIVITY_LABELS[$activity] ?? $activity)
                ->filter()
                ->values();

            $body = "Nouvelle inscription Smara Discovery Experience\n\n"
                ."Nom et prénom : {$registration->full_name}\n"
                ."Ville de résidence : {$registration->city}\n"
                ."Téléphone : {$registration->phone}\n"
                ."E-mail : {$registration->email}\n"
                .'Tranche d\'âge : '.$this->label(self::AGE_GROUP_LABELS, $registration->age_group)."\n"
                .'A déjà visité Es-Smara : '.($registration->has_visited_es_smara ? 'Oui' : 'Non')."\n"
                .'Niveau d\'intérêt : '.$this->label(self::INTEREST_LEVEL_LABELS, $registration->interest_level)."\n"
                .'Nombre de participants : '.$this->label(self::PARTICIPANTS_COUNT_LABELS, $registration->participants_count)."\n"
                .'Durée préférée : '.$this->label(self::PREFERRED_DURATION_LABELS, $registration->preferred_duration)."\n"
                ."Ville de départ : {$registration->departure_city}\n"
                ."Budget : {$registration->budget}\n"
                .'Activités sélectionnées : '.($activities->isNotEmpty() ? $activities->implode(', ') : '-')."\n"
                .'Informé(e) en priorité de la première date : '.($registration->notify_first_date ? 'Oui' : 'Non')."\n"
                .'Date de soumission : '.($registration->created_at?->format('Y-m-d H:i:s') ?? '-');

            $result = $this->emailDeliveryService->send([
                'to_email' => $recipient,
                'subject' => "Nouvelle inscription Smara Discovery : {$registration->full_name}",
                'text_content' => $body,
                'reply_to_email' => $registration->email,
                'reply_to_name' => $registration->full_name,
                'tags' => ['smara-discovery-registration'],
                'headers' => [
                    'X-Send-Trace-Id' => $sendTraceId,
                ],
            ]);

            if (! $result->success) {
                Log::warning('Smara discovery registration notification mail failed', [
                    'send_trace_id' => $sendTraceId,
                    'registration_id' => $event->registrationId,
                    'recipient' => $recipient,
                    'status' => $result->status,
                    'message_id' => $result->messageId,
                    'response_status' => $result->errorPayload['status'] ?? null,
                    'error' => $result->normalizedErrorText(),
                    'error_payload' => $result->errorPayload,
                ]);

                return;
            }

            Log::info('Smara discovery registration notification mail delivery succeeded', [
                'send_trace_id' => $sendTraceId,
                'registration_id' => $event->registrationId,
                'recipient' => $recipient,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'brevo_message_id' => $result->messageId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Smara discovery registration notification mail failed', [
                'send_trace_id' => $sendTraceId,
                'registration_id' => $event->registrationId,
                'recipient' => $recipient,
                'status' => 'failed',
                'message_id' => null,
                'response_status' => null,
                'error' => $exception->getMessage(),
                'error_payload' => [],
            ]);
        }
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function label(array $labels, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return $labels[$value] ?? $value;
    }
}
