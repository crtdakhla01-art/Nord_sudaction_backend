<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$appEnv = (string) env('APP_ENV', 'local');
$jwtSecret = env('JWT_SECRET');
$isProdLike = in_array($appEnv, ['production', 'staging'], true);

if ($isProdLike && (! is_string($jwtSecret) || trim($jwtSecret) === '')) {
    throw new RuntimeException('Authentication configuration is invalid.');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: false)
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        // This is a pure API backend — there is no web login route.
        // Returning null prevents Laravel from trying to redirect to route('login')
        // and lets the exception handler return a proper 401 JSON response instead.
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'extract.jwt' => \App\Http\Middleware\ExtractJwtFromCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $mappedErrors = \App\Support\ValidationErrorKeys::fromValidationException($e);
                    return response()->json([
                        'success' => false,
                        'error_key' => \App\Support\ValidationErrorKeys::firstErrorKey($mappedErrors),
                        'errors' => $mappedErrors,
                    ], $e->status);
                }

                $status = match (true) {
                    $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                    $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                    method_exists($e, 'status') => $e->status(),
                    default => 500,
                };

                $message = match ($status) {
                    401 => 'Unauthenticated.',
                    403 => 'Forbidden.',
                    404 => 'Not found.',
                    405 => 'Method not allowed.',
                    419 => 'Page expired.',
                    422 => 'The given data was invalid.',
                    429 => 'Too many requests.',
                    default => 'Server error',
                };

                return response()->json([
                    'message' => $message,
                ], $status >= 400 ? $status : 500);
            }
        });
    })->create();
