<?php

namespace App\Providers;

use App\Services\CompanyService;
use App\Services\FileUploadService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        // Register services
        $this->app->singleton(FileUploadService::class);
        $this->app->singleton(CompanyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
