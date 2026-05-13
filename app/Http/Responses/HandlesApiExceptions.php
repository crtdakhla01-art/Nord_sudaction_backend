<?php

namespace App\Http\Responses;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use App\Services\ValidationKeyMapper;

/**
 * Global exception handling via trait
 * Use in controllers or globally in exception handler
 */
trait HandlesApiExceptions
{
    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        $mappedErrors = ValidationKeyMapper::fromValidationException($e);

        return ApiResponse::validationError($mappedErrors);
    }

    /**
     * Handle generic exceptions
     * Logs real exception but returns safe translation key to user
     */
    protected function handleException(\Exception $e): JsonResponse
    {
        // Log the real exception for debugging
        \Log::error('API Exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Return safe message to user
        return ApiResponse::serverError('api.error_server_error');
    }

    /**
     * Handle authentication failures
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
