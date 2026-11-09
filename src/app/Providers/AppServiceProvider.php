<?php

namespace App\Providers;

use App\View\Composers\AdminMenuComposer;
use App\View\Composers\CarritoComposer;
use App\View\Composers\EncabezadoComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('api', fn ($request) => Limit::perMinute(60)->by($request->user()?->id ?? $request->ip()));

        View::composer('components.admin.layout', AdminMenuComposer::class);
        View::composer('components.tienda.encabezado', EncabezadoComposer::class);
        View::composer('components.tienda.carrito-panel', CarritoComposer::class);
    }
}
