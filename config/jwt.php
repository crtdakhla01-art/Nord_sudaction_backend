<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => max(1, (int) env('JWT_TTL', 43200)),
    'refresh_ttl' => max(1, (int) env('JWT_REFRESH_TTL', 20160)),
    'algo' => env('JWT_ALGO', 'HS256'),
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
    'leeway' => max(0, (int) env('JWT_LEEWAY', 0)),
    'lock_subject' => true,
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'blacklist_grace_period' => max(0, (int) env('JWT_BLACKLIST_GRACE_PERIOD', 0)),
    'decrypt_cookies' => false,
    'cookie_key_name' => 'token',
    'providers' => [
        'jwt' => PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];