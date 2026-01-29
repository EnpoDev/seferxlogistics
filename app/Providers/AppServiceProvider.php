<?php

namespace App\Providers;

use App\Events\CourierArrived;
use App\Events\CourierLocationUpdated;
use App\Events\CourierStatusChanged;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\PoolOrderAdded;
use App\Events\PoolOrderAssigned;
use App\Listeners\AutoAssignCourierListener;
use App\Listeners\CreateAuditLogListener;
use App\Listeners\SendCustomerNotificationListener;
use App\Listeners\SendExternalWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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

        // Define Passport OAuth scopes
        Passport::tokensCan([
            'external-orders' => 'Harici siparişleri yönetme yetkisi',
        ]);

        // Register event listeners
        // OrderStatusUpdated listeners
        Event::listen(OrderStatusUpdated::class, SendExternalWebhook::class);
        Event::listen(OrderStatusUpdated::class, SendCustomerNotificationListener::class);
        Event::listen(OrderStatusUpdated::class, CreateAuditLogListener::class);

        // CourierLocationUpdated listeners
        Event::listen(CourierLocationUpdated::class, CreateAuditLogListener::class);

        // CourierStatusChanged listeners
        Event::listen(CourierStatusChanged::class, CreateAuditLogListener::class);

        // OrderCreated listeners
        Event::listen(OrderCreated::class, SendCustomerNotificationListener::class);
        Event::listen(OrderCreated::class, CreateAuditLogListener::class);
        Event::listen(OrderCreated::class, AutoAssignCourierListener::class);

        // CourierArrived listeners
        Event::listen(CourierArrived::class, SendCustomerNotificationListener::class);

        // PoolOrderAdded listeners
        Event::listen(PoolOrderAdded::class, CreateAuditLogListener::class);

        // PoolOrderAssigned listeners
        Event::listen(PoolOrderAssigned::class, SendCustomerNotificationListener::class);
        Event::listen(PoolOrderAssigned::class, CreateAuditLogListener::class);

        // Telefon numarasi formatlama directive'i
        Blade::directive('phone', function ($expression) {
            return "<?php echo \App\Helpers\PhoneFormatter::format($expression); ?>";
        });

        // Subscription feature check directive
        // Usage: @canUseFeature('feature_name') ... @endcanUseFeature
        Blade::if('canUseFeature', function (string $feature) {
            $user = auth()->user();
            return $user && $user->canUseFeature($feature);
        });

        // Check if user has any active subscription
        // Usage: @hasSubscription ... @endhasSubscription
        Blade::if('hasSubscription', function () {
            $user = auth()->user();
            return $user && $user->hasEffectiveSubscription();
        });
    }
}
