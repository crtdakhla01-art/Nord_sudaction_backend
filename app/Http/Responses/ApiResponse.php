<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response with optional data and message key
     */
    public static function success(
        string $messageKey = 'api.success_operation',
        mixed $data = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message_key' => $messageKey,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response with translation key
     */
    public static function error(
        string $errorKey = 'api.error_server_error',
        mixed $data = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'error_key' => $errorKey,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        array $errors,
        string $errorKey = 'api.error_validation_failed'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error_key' => $errorKey,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $errorKey = 'api.error_unauthorized'
    ): JsonResponse {
        return self::error($errorKey, null, 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        string $errorKey = 'api.error_forbidden'
    ): JsonResponse {
        return self::error($errorKey, null, 403);
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $errorKey = 'api.error_not_found'
    ): JsonResponse {
        return self::error($errorKey, null, 404);
    }

    /**
     * Server error response (hides real error from user)
     */
    public static function serverError(
        string $errorKey = 'api.error_server_error'
    ): JsonResponse {
        return self::error($errorKey, null, 500);
    }
}
