<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('formatQty', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::formatQty($expression); ?>";
        });

        Blade::directive('formatRupiah', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::formatRupiah($expression); ?>";
        });
    }
}
