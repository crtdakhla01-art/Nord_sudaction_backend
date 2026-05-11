<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly int $opportunityId)
    {
    }
}
