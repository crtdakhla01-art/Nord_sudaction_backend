<?php

namespace App\Listeners;

use App\Events\OtpCodeGenerated;
use App\Models\User;
use App\Services\Email\EmailDeliveryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendOtpCodeEmail
{
    public function __construct(
        private readonly EmailDeliveryService $emailDeliveryService,
    ) {
    }

    public function handle(OtpCodeGenerated $event): void
    {
        $sendTraceId = trim((string) ($event->sendTraceId ?? ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::debug('OTP listener started', [
            'send_trace_id' => $sendTraceId,
            'user_id' => $event->userId,
            'code_length' => strlen($event->plainCode),
            'queue_connection' => config('queue.default'),
        ]);

        $dedupeKey = 'otp-mail-sent:'.$event->userId.':'.$event->plainCode;

        // Idempotency guard: if the same OTP event is dispatched twice,
        // only the first handler execution sends the email.
        if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
            Log::info('Duplicate OTP mail skipped by dedupe guard', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
            ]);

            return;
        }

        $user = User::query()->find($event->userId);

        if (! $user) {
            Log::warning('OTP listener user missing', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
            ]);

            return;
        }

        Log::debug('OTP listener recipient resolved', [
            'send_trace_id' => $sendTraceId,
            'user_id' => $event->userId,
            'email' => $user->email,
        ]);

        try {
            Log::debug('OTP listener calling EmailDeliveryService', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
                'email' => $user->email,
            ]);

            $result = $this->emailDeliveryService->send([
                'to_email' => $user->email,
                'subject' => 'Votre code de vérification OTP',
                'text_content' => "Votre code de vérification est : {$event->plainCode}",
                'tags' => ['otp'],
                'headers' => [
                    'X-Send-Trace-Id' => $sendTraceId,
                ],
            ]);

            Log::debug('OTP listener EmailDeliveryResult received', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
                'email' => $user->email,
                'success' => $result->success,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'error_payload' => $result->errorPayload,
            ]);

            if (! $result->success) {
                Log::warning('OTP mail send failed', [
                    'send_trace_id' => $sendTraceId,
                    'user_id' => $event->userId,
                    'email' => $user->email,
                    'status' => $result->status,
                    'message_id' => $result->messageId,
                    'response_status' => $result->errorPayload['status'] ?? null,
                    'error' => $result->normalizedErrorText(),
                    'error_payload' => $result->errorPayload,
                ]);

                return;
            }

            Log::info('OTP mail delivery succeeded', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
                'email' => $user->email,
                'status' => $result->status,
                'message_id' => $result->messageId,
                'brevo_message_id' => $result->messageId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('OTP mail send failed', [
                'send_trace_id' => $sendTraceId,
                'user_id' => $event->userId,
                'email' => $user->email,
                'status' => 'failed',
                'message_id' => null,
                'response_status' => null,
                'error' => $exception->getMessage(),
                'error_payload' => [],
            ]);
        }
    }
}
