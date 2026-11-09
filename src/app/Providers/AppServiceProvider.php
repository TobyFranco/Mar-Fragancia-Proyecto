<?php

namespace App\Providers;

use App\View\Composers\AdminMenuComposer;
use App\View\Composers\CarritoComposer;
use App\View\Composers\EncabezadoComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('api-login', function ($request) {
            $email = $request->input('email');

            return Limit::perMinute(5)->by($request->ip().'|'.(is_string($email) ? Str::lower(trim($email)) : ''));
        });
        RateLimiter::for('api', fn ($request) => Limit::perMinute(60)->by($request->user()?->id ?? $request->ip()));

        View::composer('components.admin.layout', AdminMenuComposer::class);
        View::composer('components.tienda.encabezado', EncabezadoComposer::class);
        View::composer('components.tienda.carrito-panel', CarritoComposer::class);
    }
}
