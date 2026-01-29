<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use App\Models\BranchSetting;
use Illuminate\Support\Facades\Log;

class GeofencingService
{
    protected const DEFAULT_RADIUS_METERS = 50;
    protected const EARTH_RADIUS_METERS = 6371000;

    /**
     * Kurye konumunun geofence içinde olup olmadığını kontrol et
     */
    public function isInsideGeofence(
        float $courierLat,
        float $courierLng,
        float $targetLat,
        float $targetLng,
        ?int $radiusMeters = null
    ): bool {
        $radiusMeters = $radiusMeters ?? self::DEFAULT_RADIUS_METERS;

        $distance = $this->calculateDistanceMeters($courierLat, $courierLng, $targetLat, $targetLng);

        return $distance <= $radiusMeters;
    }

    /**
     * Kurye için varış kontrolü yap
     */
    public function checkArrival(Courier $courier, Order $order): array
    {
        // Kurye konum bilgisi yoksa kontrol yapılamaz
        if (!$courier->lat || !$courier->lng) {
            return [
                'arrived' => false,
                'reason' => 'Kurye konumu bilinmiyor',
            ];
        }

        // Sipariş konum bilgisi yoksa kontrol yapılamaz
        if (!$order->lat || !$order->lng) {
            return [
                'arrived' => false,
                'reason' => 'Sipariş konumu bilinmiyor',
            ];
        }

        // Sipariş durumu uygun değilse kontrol yapma
        if ($order->status !== Order::STATUS_ON_DELIVERY) {
            return [
                'arrived' => false,
                'reason' => 'Sipariş henüz yolda değil',
            ];
        }

        // Geofence yarıçapını al
        $radius = $this->getGeofenceRadius($order->branch_id);

        // Mesafeyi hesapla
        $distance = $this->calculateDistanceMeters(
            $courier->lat,
            $courier->lng,
            $order->lat,
            $order->lng
        );

        $arrived = $distance <= $radius;

        return [
            'arrived' => $arrived,
            'distance' => round($distance),
            'radius' => $radius,
            'courier_location' => [
                'lat' => $courier->lat,
                'lng' => $courier->lng,
            ],
            'target_location' => [
                'lat' => $order->lat,
                'lng' => $order->lng,
            ],
        ];
    }

    /**
     * Otomatik varış event'i tetikle
     */
    public function triggerArrivalEvent(Order $order, Courier $courier): bool
    {
        // Zaten varış kaydedilmişse tekrar kaydetme
        if ($order->arrived_at) {
            return false;
        }

        try {
            // Siparişi güncelle
            $order->update([
                'arrived_at' => now(),
            ]);

            // Event yayınla (real-time bildirim için)
            event(new \App\Events\CourierArrived($order, $courier));

            // Müşteriye bildirim gönder
            $this->notifyCustomerArrival($order);

            Log::info('Geofence arrival triggered', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Geofence arrival trigger failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Konum güncellemesinde geofence kontrolü yap
     */
    public function processLocationUpdate(Courier $courier, float $lat, float $lng): array
    {
        $arrivedOrders = [];

        // Kuryenin aktif siparişlerini al
        $activeOrders = Order::where('courier_id', $courier->id)
            ->where('status', Order::STATUS_ON_DELIVERY)
            ->whereNull('arrived_at')
            ->get();

        foreach ($activeOrders as $order) {
            if (!$order->lat || !$order->lng) {
                continue;
            }

            $checkResult = $this->checkArrival($courier, $order);

            if ($checkResult['arrived']) {
                $triggered = $this->triggerArrivalEvent($order, $courier);
                if ($triggered) {
                    $arrivedOrders[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                    ];
                }
            }
        }

        return [
            'checked_orders' => $activeOrders->count(),
            'arrived_orders' => $arrivedOrders,
        ];
    }

    /**
     * Geofence yarıçapını al (şube ayarlarından)
     */
    public function getGeofenceRadius(?int $branchId = null): int
    {
        if ($branchId) {
            $settings = BranchSetting::where('branch_id', $branchId)->first();
            if ($settings && $settings->geofence_radius) {
                return $settings->geofence_radius;
            }
        }

        return self::DEFAULT_RADIUS_METERS;
    }

    /**
     * Müşteriye varış bildirimi gönder
     */
    protected function notifyCustomerArrival(Order $order): void
    {
        // CustomerNotificationService kullan
        try {
            $notificationService = new CustomerNotificationService();
            $notificationService->sendArrivalNotification($order);
        } catch (\Exception $e) {
            Log::warning('Customer arrival notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * İki nokta arasındaki mesafeyi metre cinsinden hesapla
     */
    public function calculateDistanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Geofence sınırlarını poligon olarak al (harita çizimi için)
     */
    public function getGeofencePolygon(float $lat, float $lng, int $radius = null): array
    {
        $radius = $radius ?? self::DEFAULT_RADIUS_METERS;
        $points = 32; // Çember için nokta sayısı
        $polygon = [];

        for ($i = 0; $i < $points; $i++) {
            $angle = ($i / $points) * 2 * M_PI;
            $dx = $radius * cos($angle);
            $dy = $radius * sin($angle);

            // Metre cinsinden değişimi koordinata çevir
            $latOffset = $dy / 111111; // 1 derece ~ 111111 metre
            $lngOffset = $dx / (111111 * cos(deg2rad($lat)));

            $polygon[] = [
                'lat' => $lat + $latOffset,
                'lng' => $lng + $lngOffset,
            ];
        }

        return $polygon;
    }
}
