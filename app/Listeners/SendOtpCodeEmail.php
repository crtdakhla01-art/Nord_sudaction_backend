<?php

namespace App\Listeners;

use App\Events\OtpCodeGenerated;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpCodeEmail
{

    public function handle(OtpCodeGenerated $event): void
    {
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
