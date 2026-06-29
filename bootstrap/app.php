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
        then: function () {
            // Configuration des route model bindings personnalisés
            \Illuminate\Support\Facades\Route::bind('bien', function ($value) {
                return \App\Models\Gesimmo::where('NumOrdre', $value)->firstOrFail();
            });
            
            \Illuminate\Support\Facades\Route::bind('localisation', function ($value) {
                return \App\Models\LocalisationImmo::where('idLocalisation', $value)->firstOrFail();
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enregistrement des middlewares personnalisés
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'inventory' => \App\Http\Middleware\CanManageInventaire::class,
            'session.timeout' => \App\Http\Middleware\CheckSessionTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
