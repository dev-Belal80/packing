<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA authentication (cookie-based) on the API middleware group.
        $middleware->statefulApi();

        // Always attach a request id for tracing API failures.
        $middleware->appendToGroup('api', \App\Http\Middleware\RequestIdMiddleware::class);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'subscription' => \App\Http\Middleware\SubscriptionActiveMiddleware::class,
            'api.permission' => \App\Http\Middleware\EnsureApiPermission::class,
            'approved' => \App\Http\Middleware\EnsureApproved::class,

            'tenant.set' => \App\Http\Middleware\SetTenantFromAuthenticatedUser::class,
            'tenant.subscription' => \App\Http\Middleware\EnsureTenantSubscriptionNotExpired::class,

            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $jsonError = function (Request $request, string $message, string $errorCode, int $status, array $extra = []) {
            $requestId = (string) ($request->attributes->get('request_id') ?: $request->header('X-Request-Id') ?: '');

            $payload = [
                'status' => 'error',
                'message' => $message,
                'error_code' => $errorCode,
                'request_id' => $requestId,
            ];

            foreach ($extra as $key => $value) {
                if ($value !== null) {
                    $payload[$key] = $value;
                }
            }

            return response()->json($payload, $status);
        };

        $exceptions->render(function (ValidationException $e, Request $request) use ($jsonError) {
            return $jsonError($request, 'Validation failed', 'validation_failed', 422, [
                'errors' => $e->errors(),
            ]);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($jsonError) {
            return $jsonError($request, 'Unauthenticated', 'unauthenticated', 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($jsonError) {
            return $jsonError($request, 'Forbidden', 'forbidden', 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($jsonError) {
            return $jsonError($request, 'Not found', 'model_not_found', 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($jsonError) {
            return $jsonError($request, 'Not found', 'not_found', 404);
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($jsonError) {
            $debug = config('app.debug') ? [
                'exception' => class_basename($e),
                'sql_state' => $e->getCode(),
            ] : null;

            Log::error('Database error', [
                'exception' => $e,
                'request_path' => $request->path(),
            ]);

            return $jsonError($request, 'Database error', 'database_error', 500, [
                'debug' => $debug,
            ]);
        });

        // Catch-all JSON handler for API routes.
        $exceptions->render(function (Throwable $e, Request $request) use ($jsonError) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $debug = config('app.debug') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null;

            Log::error('Unhandled exception', [
                'exception' => $e,
                'request_path' => $request->path(),
                'status' => $status,
            ]);

            return $jsonError(
                $request,
                $status >= 500 ? 'Server error' : ($e->getMessage() ?: 'Error'),
                $status >= 500 ? 'server_error' : 'http_error',
                $status,
                ['debug' => $debug]
            );
        });

        $exceptions->dontReport([]);
    })->create();
