<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $eventId,
        public readonly ?string $sendTraceId = null,
    ) {
    }
}