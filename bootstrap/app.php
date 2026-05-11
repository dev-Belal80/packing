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
        // Sanctum SPA authentication (cookie-based) on the API middleware group.
        $middleware->statefulApi();

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
        $exceptions->dontReport([]);
    })->create();
