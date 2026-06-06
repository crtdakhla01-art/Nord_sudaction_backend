<?php

namespace App\Http\Controllers\Api;

use App\Events\ContactMessageCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $sendTraceId = trim((string) $request->header('X-Send-Trace-Id', ''));
        if ($sendTraceId === '') {
            $sendTraceId = (string) Str::uuid();
        }

        Log::info('Contact message flow started', [
            'send_trace_id' => $sendTraceId,
            'ip' => $request->ip(),
        ]);

        $payload = $request->validated();
        $payload['phone'] = isset($payload['phone']) && trim((string) $payload['phone']) !== ''
            ? $payload['phone']
            : null;

        $contactMessage = ContactMessage::query()->create($payload);

        Log::info('Contact message created', [
            'send_trace_id' => $sendTraceId,
            'contact_message_id' => $contactMessage->id,
        ]);

        event(new ContactMessageCreated($contactMessage->id, $sendTraceId));

        Log::info('Contact message event dispatched', [
            'send_trace_id' => $sendTraceId,
            'contact_message_id' => $contactMessage->id,
            'event' => ContactMessageCreated::class,
        ]);

        return response()->json([
            'success' => true,
            'message_key' => 'api.success_operation',
            'data' => $contactMessage,
        ], 201);
    }
}
