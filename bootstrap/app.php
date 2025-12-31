<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware (tum isteklere uygulanir)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web middleware
        $middleware->web(append: [
            \App\Http\Middleware\PanelMiddleware::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'webhook.validate' => \App\Http\Middleware\ValidateWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
