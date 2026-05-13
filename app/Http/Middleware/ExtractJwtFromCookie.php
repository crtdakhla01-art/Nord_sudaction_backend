<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'token_masked' => is_string($token) && $token !== '' ? $this->maskToken($token) : null,
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
        $enabled = app()->environment(['local', 'development'])
            || config('app.debug')
            || (bool) env('AUTH_DEBUG', false);

        if (! $enabled) {
            return;
        }

        Log::debug($message, $context);
    }

    private function maskToken(string $token): string
    {
        if (strlen($token) < 10) {
            return '***';
        }

        return substr($token, 0, 6).'...'.substr($token, -4);
    }
}
