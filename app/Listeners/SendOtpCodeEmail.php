<?php

namespace App\Listeners;

use App\Events\OtpCodeGenerated;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpCodeEmail
{

    public function handle(OtpCodeGenerated $event): void
    {
        $dedupeKey = 'otp-mail-sent:'.$event->userId.':'.$event->plainCode;

        // Idempotency guard: if the same OTP event is dispatched twice,
        // only the first handler execution sends the email.
        if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
            Log::info('Duplicate OTP mail skipped by dedupe guard', [
                'user_id' => $event->userId,
            ]);

            return;
        }

        $user = User::query()->find($event->userId);

        if (! $user) {
            return;
        }

        try {
            Mail::raw("Your verification code is: {$event->plainCode}", function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Your OTP Verification Code');
            });
        } catch (\Throwable $exception) {
            Log::warning('OTP mail send failed', [
                'user_id' => $event->userId,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
