<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return structured JSON for API requests so the real error
        // is visible in the console instead of the generic "Server Error" page.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status  = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $payload = [
                    'error'     => true,
                    'status'    => $status,
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ];
                // Include previous exception when present (e.g. PDOException inside QueryException)
                if ($e->getPrevious()) {
                    $payload['caused_by'] = [
                        'exception' => get_class($e->getPrevious()),
                        'message'   => $e->getPrevious()->getMessage(),
                    ];
                }
                return response()->json($payload, $status >= 400 ? $status : 500);
            }
        });
    })->create();
