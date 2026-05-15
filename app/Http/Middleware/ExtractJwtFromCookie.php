<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtractJwtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('token');
        $hasAuthHeader = $request->headers->has('Authorization');

        $this->debugLog('[SESSION] ExtractJwtFromCookie middleware executed', [
            'path' => $request->path(),
            'has_token_cookie' => is_string($token) && $token !== '',
            'has_authorization_header' => $hasAuthHeader,
        ]);

        if (is_string($token) && $token !== '' && ! $hasAuthHeader) {
            $request->headers->set('Authorization', 'Bearer '.$token);

            $this->debugLog('[SESSION] Authorization header injected from cookie', [
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }

    private function debugLog(string $message, array $context = []): void
    {
        return;
    }

}
