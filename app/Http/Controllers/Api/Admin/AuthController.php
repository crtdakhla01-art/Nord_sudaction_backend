<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OtpCodeGenerated;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 400);
        }

        $credentials = $validator->validated();

        $user = User::query()
            ->with('role')
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            Log::info('Login failed: invalid credentials', ['email' => $credentials['email']]);

            return response()->json([
                'message' => 'The provided credentials are invalid.',
            ], 401);
        }

        OtpCode::query()->where('user_id', $user->id)->delete();

        $plainCode = (string) random_int(100000, 999999);

        OtpCode::query()->create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(5),
        ]);

        event(new OtpCodeGenerated($user->id, $plainCode));

        Log::info('OTP generated', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();

        $user = User::query()
            ->with('role')
            ->where('email', $data['email'])
            ->first();

        if (! $user) {
            Log::info('OTP verify failed: user not found', ['email' => $data['email']]);

            return response()->json([
                'message' => 'The provided OTP is invalid.',
            ], 401);
        }

        $otp = OtpCode::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (! $otp || now()->greaterThan($otp->expires_at) || ! Hash::check($data['code'], $otp->code)) {
            if ($otp && now()->greaterThan($otp->expires_at)) {
                $otp->delete();
            }

            Log::info('OTP verify failed: invalid or expired', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'The provided OTP is invalid or expired.',
            ], 401);
        }

        $otp->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
