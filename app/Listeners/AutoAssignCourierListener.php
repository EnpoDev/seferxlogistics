<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\AIOrderDistributionService;
use Illuminate\Support\Facades\Log;

class AutoAssignCourierListener
{
    public function __construct(
        protected AIOrderDistributionService $distributionService
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Zaten kurye atanmışsa veya iptal edilmişse işlem yapma
        if ($order->courier_id || $order->status === 'cancelled') {
            return;
        }

        // RestaurantConnection'dan auto_assign ayarını kontrol et
        $connection = $order->restaurantConnection;
        if (!$connection?->auto_assign_courier) {
            Log::info('Auto assign courier disabled for connection', [
                'order_id' => $order->id,
                'connection_id' => $connection?->id,
            ]);
            return;
        }

        // Restoran çalışma saatlerini kontrol et (SeferXYemek'ten gelen saatler)
        if ($connection && !$connection->isOpen()) {
            Log::info('Restaurant is closed, skipping auto courier assignment', [
                'order_id' => $order->id,
                'connection_id' => $connection->id,
            ]);
            return;
        }

        // En uygun kuryeyi bul
        $bestCourier = $this->distributionService->findBestCourier($order);

        if ($bestCourier) {
            $order->assignCourier($bestCourier);
            $bestCourier->incrementActiveOrders();

            Log::info('Courier auto-assigned to external order', [
                'order_id' => $order->id,
                'courier_id' => $bestCourier->id,
                'courier_name' => $bestCourier->name,
            ]);
        } else {
            Log::info('No suitable courier found for auto-assignment', [
                'order_id' => $order->id,
            ]);
        }
    }
}
