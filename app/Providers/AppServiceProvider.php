<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix pour MySQL : limite la longueur des chaînes pour les index
        // MySQL avec utf8mb4 a une limite de 767 bytes pour les index
        // Utilisation de 100 caractères pour être sûr (100 * 4 = 400 bytes < 1000)
        Schema::defaultStringLength(100);
    }
}
