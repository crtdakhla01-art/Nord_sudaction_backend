<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        $allowedRoles = array_map('trim', explode(',', $roles));

        if (! $user) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! in_array($user->role?->name, $allowedRoles, true)) {
            return new JsonResponse([
                'message' => 'Unauthorized.',
            ], 403);
        }

        return $next($request);
    }
}
