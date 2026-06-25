<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/integrations.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'super.admin' => EnsureSuperAdmin::class,
            'honeypot' => ProtectAgainstSpam::class,
        ]);
        $middleware->appendToGroup('web', ProtectAgainstSpam::class);

        // CORS — allow any origin to read from the public REST API
        $middleware->api(prepend: [
            HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Headless API: always render JSON for api/* (never redirect to a login page).
        $exceptions->shouldRenderJsonWhen(
            fn ($request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->report(function (Throwable $e): void {
            if (app()->bound('sentry') && app()->environment('production')) {
                app('sentry')->captureException($e);
            }
        });
    })->create();
