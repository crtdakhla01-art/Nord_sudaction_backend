<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NewsletterSubscriberController extends Controller
{
    public function store(StoreNewsletterSubscriberRequest $request): JsonResponse
    {
        try {
            $subscriber = NewsletterSubscriber::query()->create([
                ...$request->validated(),
                'unsubscribe_token' => NewsletterSubscriber::generateUnsubscribeToken(),
                'unsubscribed_at' => null,
                'is_suppressed' => false,
                'suppressed_at' => null,
                'suppression_reason' => null,
            ]);
        } catch (QueryException $exception) {
            // MySQL/MariaDB duplicate key error code.
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'error_key' => 'validation.email_taken',
                    'errors' => [
                        'email' => ['validation.email_taken'],
                    ],
                ], 422);
            }

            Log::error('Newsletter subscription query failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error_key' => 'api.error_server_error',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $subscriber,
        ], 201);
    }
}
