<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Anti mixed-content sur Render (TLS termine au proxy) :
        // force https si env prod, APP_URL en https, ou FORCE_HTTPS=true
        // ecrit par l'entrypoint Docker (disque Render persistant, .env fige).
        if (app()->environment('production')
            || str_starts_with((string) config('app.url'), 'https://')
            || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceScheme('https');
        }
    }
}
