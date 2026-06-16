<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Unified JSON error responses for the public API surface.
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $this->wantsJson($request)) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => response()->json([
                    'message' => 'Resource not found.',
                ], 404),

                $e instanceof ThrottleRequestsException => response()->json([
                    'message' => 'Too many requests. Please try again shortly.',
                ], 429, array_filter([
                    'Retry-After' => $e->getHeaders()['Retry-After'] ?? null,
                ])),

                $e instanceof AuthenticationException => response()->json([
                    'message' => 'Unauthenticated.',
                ], 401),

                $e instanceof HttpExceptionInterface => response()->json([
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Request failed.',
                ], $e->getStatusCode(), $e->getHeaders()),

                default => null, // Let Laravel render its default for everything else.
            };
        });
    }

    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->is('api/*')
            || $request->wantsJson();
    }
}
