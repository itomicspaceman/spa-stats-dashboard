<?php

namespace App\Providers;

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
        // Make ChartHelper available in Blade views
        \Illuminate\Support\Facades\Blade::directive('chartRelevant', function ($expression) {
            return "<?php if (\App\Helpers\ChartHelper::isChartRelevant($expression, request()->get('filter'))): ?>";
        });
        
        \Illuminate\Support\Facades\Blade::directive('endchartRelevant', function () {
            return "<?php endif; ?>";
        });
    }
}
