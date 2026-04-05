<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$apiUrl = 'https://api.counterapi.dev/v2/conseil-tourisme-dakhlas-team-3599/nordsudaction_visitore/up';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n",
    ],
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'code' => 502,
        'message' => 'Unable to reach Counter API.',
    ]);
    exit;
}

echo $response;
