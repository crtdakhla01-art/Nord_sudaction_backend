<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;

class UnsubscribeController extends Controller
{
    public function show(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->first();

        if (! $subscriber) {
            abort(404);
        }

        if ($subscriber->unsubscribed_at === null) {
            $updates = [
                'unsubscribed_at' => now(),
                'consent' => false,
            ];

            if (array_key_exists('is_subscribed', $subscriber->getAttributes())) {
                $updates['is_subscribed'] = false;
            }

            $subscriber->update($updates);

            return view('newsletter.unsubscribe-status', [
                'title' => 'Desabonnement confirme',
                'message' => 'Vous avez ete desabonne avec succes de notre newsletter.',
            ]);
        }

        return view('newsletter.unsubscribe-status', [
            'title' => 'Deja desabonne',
            'message' => 'Vous etes deja desabonne de notre newsletter.',
        ]);
    }
}
