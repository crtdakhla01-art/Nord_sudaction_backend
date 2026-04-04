<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['phone'] = isset($payload['phone']) && trim((string) $payload['phone']) !== ''
            ? $payload['phone']
            : null;

        $contactMessage = ContactMessage::query()->create($payload);

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');
        try {
            Mail::raw(
                "New contact message\n\nName: {$payload['name']}\nEmail: {$payload['email']}\nPhone: " . ($payload['phone'] ?? '-') . "\nObject: {$payload['object']}\n\nMessage:\n{$payload['message']}",
                function ($message) use ($payload, $recipient): void {
                    $message->to($recipient)
                        ->replyTo($payload['email'], $payload['name'])
                        ->subject("New contact form submission: {$payload['object']}");
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('Contact message mail send failed', [
                'contact_message_id' => $contactMessage->id,
                'sender_email' => $payload['email'],
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Message saved, but email delivery failed.',
                'data' => $contactMessage,
            ], 202);
        }

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => $contactMessage,
        ], 201);
    }
}
