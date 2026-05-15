<?php

namespace App\Http\Responses;

use App\Services\ValidationKeyMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Global exception handling via trait.
 * Use in controllers or globally in exception handler.
 */
trait HandlesApiExceptions
{
    /**
     * Handle validation exceptions.
     */
    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        $mappedErrors = ValidationKeyMapper::fromValidationException($e);

        return ApiResponse::validationError($mappedErrors);
    }

    /**
     * Handle generic exceptions.
     * Logs only high-level metadata and returns a safe translation key to the client.
     */
    protected function handleException(\Exception $e): JsonResponse
    {
        \Log::error('API Exception', [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'code' => $e->getCode(),
        ]);

        return ApiResponse::serverError('api.error_server_error');
    }

    /**
     * Handle authentication failures.
     */
    protected function handleAuthenticationFailure(string $reason = 'invalid'): JsonResponse
    {
        $keyMap = [
            'invalid' => 'api.error_invalid_credentials',
            'expired' => 'api.error_session_expired',
            'banned' => 'api.error_account_banned',
        ];

        $key = $keyMap[$reason] ?? 'api.error_unauthorized';

        return ApiResponse::unauthorized($key);
    }
}
