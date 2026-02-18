<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\LogService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class AppExceptionHandler
{
    public static function report(Throwable $e): void
    {
        if (self::shouldNotReport($e)) {
            return;
        }

        LogService::exception($e, null, [
            'report' => true,
        ]);
    }

    public static function render(Throwable $e, Request $request): mixed
    {
        if ($e instanceof AuthenticationException) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Endpoint not found',
            ], 404);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'HTTP error',
            ], $e->getStatusCode());
        }

        LogService::exception($e, 'Unhandled exception');

        return response()->json([
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'exception' => config('app.debug') ? get_class($e) : null,
        ], 500);
    }

    protected static function shouldNotReport(Throwable $e): bool
    {
        $dontReport = [
            ValidationException::class,
        ];

        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }
}
