<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmaraDiscoveryRegistrationSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $registrationId,
        public readonly ?string $sendTraceId = null,
    ) {
    }
}
