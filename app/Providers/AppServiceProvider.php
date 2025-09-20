<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
        Route::middleware('api')
        ->prefix('api') // All API routes will start with /api
        ->group(base_path('routes/api.php'));

        Route::middleware('api')
        ->prefix('api/onboarding') // Onboarding routes start with /api/onboarding
        ->group(base_path('routes/api-onboarding.php'));
        
        Route::middleware('api')
        ->prefix('api/auth')
        ->group(base_path('routes/api-auth.php'));

        Route::middleware('api')
        ->prefix('api/sale')
        ->group(base_path('routes/api-sale.php'));

        Route::middleware('api')
        ->prefix('api/licenses')
        ->group(base_path('routes/api-licenses.php'));

           Route::middleware('api')
        ->prefix('api/admin-panel')
        ->group(base_path('routes/api-admin-panel.php'));

             Route::middleware('api')
        ->prefix('api/accounts')
        ->group(base_path('routes/api-accounts.php'));
    }
}
 