<?php

declare(strict_types=1);

// This file is deprecated. Use /api/visitor-up instead.
// It remains for backwards compatibility but returns 410 Gone.

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'code' => 410,
    'message' => 'This endpoint is deprecated. Use /api/visitor-up instead.',
]);

exit;
