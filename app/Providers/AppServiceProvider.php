<?php

namespace App\Providers;

use App\Services\VisionIA\ProveedorVisionIA;
use App\Services\VisionIA\ServicioVisionIA;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ServicioVisionIA::class, ProveedorVisionIA::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
