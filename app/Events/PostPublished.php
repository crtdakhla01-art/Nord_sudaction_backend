<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $postId,
        public readonly ?string $sendTraceId = null,
    )
    {
    }
}
