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
            $subscriber = NewsletterSubscriber::query()->create($request->validated());
        } catch (QueryException $exception) {
            // MySQL/MariaDB duplicate key error code.
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'message' => 'This email is already subscribed.',
                    'errors' => [
                        'email' => ['This email is already subscribed.'],
                    ],
                ], 422);
            }

            Log::error('Newsletter subscription query failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Newsletter service temporarily unavailable.',
            ], 503);
        }

        return response()->json([
            'message' => 'Subscription completed successfully.',
            'data' => $subscriber,
        ], 201);
    }
}
