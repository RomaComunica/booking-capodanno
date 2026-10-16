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
        // Track partner ID across all web routes
        $middleware->web(append: [
            \App\Http\Middleware\TrackPartner::class,
        ]);

        // Exempt iframe checkout and payment routes from CSRF mismatch
        $middleware->validateCsrfTokens(except: [
            'cart/*',
            'checkout/*',
            'payment/*',
            'paypal/*',
            'nexi/*',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
