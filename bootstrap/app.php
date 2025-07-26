<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies
        $middleware->trustProxies('*');
        
        // Skip CSRF for API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Add CORS middleware to the global middleware stack
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $status = method_exists($e, 'getStatusCode') 
                    ? $e->getStatusCode() 
                    : 500;
                
                $response = [
                    'message' => $e->getMessage(),
                ];

                if (config('app.debug')) {
                    $response['exception'] = get_class($e);
                    $response['trace'] = $e->getTrace();
                }

                return response()->json($response, $status);
            }
        });
    })->create();
