<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
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
        // Set Turkish locale for Carbon dates
        Carbon::setLocale('tr');
        setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'turkish');

        // Telefon numarasi formatlama directive'i
        Blade::directive('phone', function ($expression) {
            return "<?php echo \App\Helpers\PhoneFormatter::format($expression); ?>";
        });
    }
}
