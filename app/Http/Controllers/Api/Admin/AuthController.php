<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OtpCodeGenerated;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Support\ValidationErrorKeys;
use App\Support\ValidationPatterns;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OTP_TTL_MINUTES = 5;

    private const OTP_MAX_ATTEMPTS = 5;

    private const OTP_REUSE_WINDOW_SECONDS = 30;

    private const JWT_COOKIE_TTL_MINUTES = 43200;

    public function login(Request $request): JsonResponse
    {
        $normalizedEmail = ValidationPatterns::normalizeEmail($request->input('email'));
        $request->merge(['email' => $normalizedEmail]);

        $this->debugLog('[AUTH] Login request received', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'email_masked' => $this->maskEmail((string) $request->input('email', '')),
        ]);

        $validator = Validator::make($request->all(), [
            'email' => ValidationPatterns::emailRules(true),
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $credentials = $validator->validated();

        $user = User::query()
            ->with('role')
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            Log::info('[AUTH] Login failed: invalid credentials', [
                'email_masked' => $this->maskEmail($credentials['email']),
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_invalid_credentials', 401);
        }

        $this->debugLog('[AUTH] Credentials validated; OTP challenge required', [
            'user_id' => $user->id,
            'email_masked' => $this->maskEmail($user->email),
        ]);

        $lock = Cache::lock('otp-login-'.$user->id, 10);

        if (! $lock->get()) {
            $existingChallengeId = Cache::get($this->otpUserChallengeKey($user->id));

            Log::warning('[OTP] OTP generation lock hit (possible concurrent requests)', [
                'user_id' => $user->id,
                'challenge_id' => $existingChallengeId,
            ]);

            return $this->successResponse('api.success_otp_sent', [
                'otp_required' => true,
                'challenge_id' => $existingChallengeId,
            ]);
        }

        try {
            $existingChallengeId = Cache::get($this->otpUserChallengeKey($user->id));

            $latestOtp = OtpCode::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            // Prevent duplicate OTP emails when login is submitted twice quickly.
            if ($latestOtp
                && $latestOtp->created_at
                && now()->lessThan($latestOtp->expires_at)
                && now()->diffInSeconds($latestOtp->created_at) < self::OTP_REUSE_WINDOW_SECONDS) {
                Log::info('OTP reuse window hit', [
                    'user_id' => $user->id,
                    'email_masked' => $this->maskEmail($user->email),
                    'challenge_id' => $existingChallengeId,
                ]);

                if (! is_string($existingChallengeId) || $existingChallengeId === '') {
                    $existingChallengeId = (string) Str::uuid();
                }

                $this->storeOtpChallenge($existingChallengeId, $user, 0);

                return $this->successResponse('api.success_otp_sent', [
                    'otp_required' => true,
                    'challenge_id' => $existingChallengeId,
                    'expires_in_seconds' => self::OTP_TTL_MINUTES * 60,
                ]);
            }

            if (is_string($existingChallengeId) && $existingChallengeId !== '') {
                Cache::forget($this->otpChallengeKey($existingChallengeId));
            }

            OtpCode::query()->where('user_id', $user->id)->delete();

            $plainCode = (string) random_int(100000, 999999);

            OtpCode::query()->create([
                'user_id' => $user->id,
                'code' => Hash::make($plainCode),
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);

            $challengeId = (string) Str::uuid();
            $this->storeOtpChallenge($challengeId, $user, 0);

            event(new OtpCodeGenerated($user->id, $plainCode));

            Log::info('OTP generated', [
                'user_id' => $user->id,
                'email_masked' => $this->maskEmail($user->email),
                'challenge_id' => $challengeId,
                'ttl_minutes' => self::OTP_TTL_MINUTES,
            ]);

            return $this->successResponse('api.success_otp_sent', [
                'otp_required' => true,
                'challenge_id' => $challengeId,
                'expires_in_seconds' => self::OTP_TTL_MINUTES * 60,
            ]);
        } finally {
            $lock->release();
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $this->debugLog('[OTP] Verification request received', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'challenge_id' => (string) $request->input('challenge_id', ''),
        ]);

        $validator = Validator::make($request->all(), [
            'challenge_id' => ['required', 'string', 'max:100'],
            'code' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();

        $challengeKey = $this->otpChallengeKey($data['challenge_id']);
        $challenge = Cache::get($challengeKey);

        if (! is_array($challenge)
            || ! isset($challenge['user_id'], $challenge['expires_at'], $challenge['attempts'])) {
            Log::info('OTP verify failed: challenge missing', [
                'challenge_id' => $data['challenge_id'],
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_otp_invalid', 401);
        }

        if (now()->timestamp >= (int) $challenge['expires_at']) {
            Cache::forget($challengeKey);
            Cache::forget($this->otpUserChallengeKey((int) $challenge['user_id']));

            Log::info('OTP verify failed: challenge expired', [
                'challenge_id' => $data['challenge_id'],
                'user_id' => (int) $challenge['user_id'],
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_otp_expired', 401);
        }

        $attempts = (int) $challenge['attempts'];
        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($challengeKey);
            Cache::forget($this->otpUserChallengeKey((int) $challenge['user_id']));
            OtpCode::query()->where('user_id', (int) $challenge['user_id'])->delete();

            Log::warning('OTP verify blocked: max attempts reached', [
                'challenge_id' => $data['challenge_id'],
                'user_id' => (int) $challenge['user_id'],
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_otp_max_attempts', 429);
        }

        $user = User::query()
            ->with('role')
            ->whereKey((int) $challenge['user_id'])
            ->first();

        if (! $user) {
            Cache::forget($challengeKey);
            Cache::forget($this->otpUserChallengeKey((int) $challenge['user_id']));

            Log::info('OTP verify failed: user not found', [
                'challenge_id' => $data['challenge_id'],
                'user_id' => (int) $challenge['user_id'],
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_unauthorized', 401);
        }

        $otp = OtpCode::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (! $otp || now()->greaterThan($otp->expires_at) || ! Hash::check($data['code'], $otp->code)) {
            $attempts++;
            $challenge['attempts'] = $attempts;
            Cache::put($challengeKey, $challenge, now()->addSeconds(max(1, ((int) $challenge['expires_at']) - now()->timestamp)));

            if ($otp && now()->greaterThan($otp->expires_at)) {
                $otp->delete();
            }

            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                Cache::forget($challengeKey);
                Cache::forget($this->otpUserChallengeKey($user->id));
                OtpCode::query()->where('user_id', $user->id)->delete();

                Log::warning('OTP verify blocked after invalid attempts', [
                    'user_id' => $user->id,
                    'challenge_id' => $data['challenge_id'],
                    'ip' => $request->ip(),
                ]);

                return $this->errorResponse('api.error_otp_max_attempts', 429);
            }

            Log::info('OTP verify failed: invalid or expired', [
                'user_id' => $user->id,
                'challenge_id' => $data['challenge_id'],
                'attempts' => $attempts,
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse('api.error_otp_invalid', 401);
        }

        $otp->delete();
        Cache::forget($challengeKey);
        Cache::forget($this->otpUserChallengeKey($user->id));

        $token = Auth::guard('jwt')->login($user);

        $this->debugLog('[AUTH] JWT issued after OTP success', [
            'user_id' => $user->id,
            'email_masked' => $this->maskEmail($user->email),
            'token_masked' => $this->maskToken($token),
        ]);

        $originHost = parse_url((string) $request->headers->get('origin', ''), PHP_URL_HOST);
        $isCrossSite = is_string($originHost) && $originHost !== '' && $originHost !== $request->getHost();
        $secureCookie = $isCrossSite ? true : $request->isSecure();
        $sameSite = ($isCrossSite && $secureCookie) ? 'None' : 'Lax';

        $this->debugLog('[SESSION] Auth cookie prepared', [
            'user_id' => $user->id,
            'cookie_name' => 'token',
            'http_only' => true,
            'secure' => $secureCookie,
            'same_site' => $sameSite,
            'cross_site' => $isCrossSite,
        ]);

        return $this->successResponse('api.success_otp_verified', [
            'user' => $user,
        ])->cookie(
            'token',
            $token,
            self::JWT_COOKIE_TTL_MINUTES,
            '/',
            null,
            $secureCookie,
            true,
            false,
            $sameSite
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->debugLog('[LOGOUT] Logout request received', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'has_token_cookie' => is_string($request->cookie('token')) && $request->cookie('token') !== '',
        ]);

        try {
            Auth::guard('jwt')->logout();
            $this->debugLog('[LOGOUT] JWT invalidation attempted');
        } catch (\Throwable $e) {
            Log::info('Logout without active JWT token', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->successResponse('api.success_logout')->withCookie(cookie()->forget('token'));
    }

    public function me(Request $request): JsonResponse
    {
        $this->debugLog('[SESSION] /admin/me called', [
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        $user = $request->user()?->load('role');

        if (! $user) {
            Log::warning('[SESSION] /admin/me unauthenticated access', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->errorResponse('api.error_unauthorized', 401);
        }

        $this->debugLog('[SESSION] /admin/me success', [
            'user_id' => $user->id,
            'role' => $user->role?->name,
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    private function otpChallengeKey(string $challengeId): string
    {
        return 'otp:challenge:'.$challengeId;
    }

    private function otpUserChallengeKey(int $userId): string
    {
        return 'otp:challenge:user:'.$userId;
    }

    private function storeOtpChallenge(string $challengeId, User $user, int $attempts): void
    {
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);
        Cache::put($this->otpChallengeKey($challengeId), [
            'user_id' => $user->id,
            'attempts' => $attempts,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        Cache::put($this->otpUserChallengeKey($user->id), $challengeId, $expiresAt);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***';
        }

        $prefix = substr($local, 0, 1);

        return $prefix.'***@'.$domain;
    }

    private function maskToken(string $token): string
    {
        if (strlen($token) < 10) {
            return '***';
        }

        return substr($token, 0, 6).'...'.substr($token, -4);
    }

    private function successResponse(string $messageKey, mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message_key' => $messageKey,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    private function errorResponse(string $errorKey, int $status = 400, mixed $data = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'error_key' => $errorKey,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    private function validationErrorResponse(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        $mappedErrors = ValidationErrorKeys::fromFailedRules($validator->failed());

        return response()->json([
            'success' => false,
            'error_key' => ValidationErrorKeys::firstErrorKey($mappedErrors),
            'errors' => $mappedErrors,
        ], 422);
    }

    private function debugLog(string $message, array $context = []): void
    {
        $enabled = app()->environment(['local', 'development'])
            || config('app.debug')
            || (bool) env('AUTH_DEBUG', false);

        if (! $enabled) {
            return;
        }

        Log::debug($message, $context);
    }
}
