<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RolePermissionMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->api(prepend: [
            //
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'admin' => AdminMiddleware::class,
            'role' => RolePermissionMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/user/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'login_url' => url('/api/login'),
                ], 401);
            }

            if ($request->is('api/admin/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'login_url' => url('/api/admin/login'),
                ], 401);
            }

            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });
    })->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php'
    )

    ->create();
