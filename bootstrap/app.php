<?php

use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            CorsMiddleware::class,
            ForceJsonResponse::class,
            SecurityHeaders::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {

            if (! ($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            // Domain exceptions -> HTTP 422
            if ($e instanceof DomainException) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            // Validation
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Authentication
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }

            // HTTP Exceptions
            if ($e instanceof HttpException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'HTTP error.',
                ], $e->getStatusCode());
            }

            // Generic server error
            $message = app()->environment('production')
                ? 'Server error. Please try again later.'
                : $e->getMessage();

            return response()->json([
                'message' => $message,
            ], 500);
        });

    })

    ->create();
