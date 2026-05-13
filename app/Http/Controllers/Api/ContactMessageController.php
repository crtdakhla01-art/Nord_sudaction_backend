<?php

namespace App\Http\Controllers\Api;

use App\Events\ContactMessageCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['phone'] = isset($payload['phone']) && trim((string) $payload['phone']) !== ''
            ? $payload['phone']
            : null;

        $contactMessage = ContactMessage::query()->create($payload);

        event(new ContactMessageCreated($contactMessage->id));

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $contactMessage,
        ], 201);
    }
}
