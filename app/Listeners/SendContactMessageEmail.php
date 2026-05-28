<?php

namespace App\Listeners;

use App\Events\ContactMessageCreated;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendContactMessageEmail
{

    public function handle(ContactMessageCreated $event): void
    {
        $contactMessage = ContactMessage::query()->find($event->contactMessageId);

        if (! $contactMessage) {
            return;
        }

        $recipient = config('mail.contact_recipient', 'contact@nordsudaction.org');

        try {
            Mail::raw(
                "Nouveau message de contact\n\nNom : {$contactMessage->name}\nE-mail : {$contactMessage->email}\nTéléphone : " . ($contactMessage->phone ?: '-') . "\nObjet : {$contactMessage->object}\n\nMessage :\n{$contactMessage->message}",
                function ($message) use ($contactMessage, $recipient): void {
                    $message->to($recipient)
                        ->replyTo($contactMessage->email, $contactMessage->name)
                        ->subject("Nouveau message de contact : {$contactMessage->object}");
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('Contact message mail send failed', [
                'contact_message_id' => $event->contactMessageId,
                'sender_email' => $contactMessage->email,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
