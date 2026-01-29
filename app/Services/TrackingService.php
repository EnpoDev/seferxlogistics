<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use Illuminate\Support\Carbon;

class TrackingService
{
    // Ortalama hızlar (km/saat)
    private const AVG_SPEED_CITY = 25;
    private const AVG_SPEED_TRAFFIC = 15;
    private const AVG_SPEED_NIGHT = 35;

    /**
     * Tahmini teslimat süresini hesapla
     */
    public function calculateETA(Order $order): ?Carbon
    {
        // Teslim edilmiş siparişler için null
        if ($order->status === 'delivered' || $order->status === 'cancelled') {
            return null;
        }

        // Kurye atanmamışsa statik tahmin
        if (!$order->courier_id) {
            return $this->getStaticETA($order);
        }

        $courier = $order->courier;
        if (!$courier || !$courier->lat || !$courier->lng) {
            return $this->getStaticETA($order);
        }

        // Kurye konumu ile teslimat noktası arası mesafe
        $distance = $this->calculateDistance(
            $courier->lat,
            $courier->lng,
            $order->lat,
            $order->lng
        );

        // Hız tahmini (trafik durumuna göre)
        $speed = $this->getEstimatedSpeed();

        // Süre hesabı (dakika)
        $travelMinutes = ($distance / $speed) * 60;

        // Durum bazlı ek süreler
        $additionalMinutes = match ($order->status) {
            'pending' => 30, // Hazırlık + teslimat
            'preparing' => 20,
            'ready' => 10, // Kurye alma süresi
            'on_delivery' => 0,
            default => 15,
        };

        $totalMinutes = ceil($travelMinutes + $additionalMinutes);

        // Siparişe kaydet
        $order->update([
            'estimated_minutes' => $totalMinutes,
            'estimated_delivery_at' => now()->addMinutes($totalMinutes),
        ]);

        return now()->addMinutes($totalMinutes);
    }

    /**
     * Statik ETA (kurye atanmamış siparişler için)
     */
    private function getStaticETA(Order $order): Carbon
    {
        $minutes = match ($order->status) {
            'pending' => 45,
            'preparing' => 35,
            'ready' => 25,
            default => 30,
        };

        return now()->addMinutes($minutes);
    }

    /**
     * İki nokta arası mesafe (Haversine formülü)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Tahmini hız (saat ve trafik durumuna göre)
     */
    private function getEstimatedSpeed(): float
    {
        $hour = now()->hour;

        // Gece saatleri (23:00 - 06:00)
        if ($hour >= 23 || $hour < 6) {
            return self::AVG_SPEED_NIGHT;
        }

        // Yoğun saatler (07:30-09:30, 17:00-19:30)
        if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
            return self::AVG_SPEED_TRAFFIC;
        }

        return self::AVG_SPEED_CITY;
    }

    /**
     * Kurye konum güncelleme
     */
    public function updateCourierLocation(Courier $courier, float $lat, float $lng): void
    {
        $courier->update([
            'lat' => $lat,
            'lng' => $lng,
        ]);

        // Aktif siparişlerin ETA'sını güncelle ve tracking event yayınla
        $activeOrders = Order::where('courier_id', $courier->id)
            ->whereIn('status', ['ready', 'on_delivery'])
            ->get();

        foreach ($activeOrders as $order) {
            $this->calculateETA($order);

            // Tracking token varsa, müşteri portalına konum güncellemesi yayınla
            if ($order->tracking_token && $order->status === 'on_delivery') {
                broadcast(new \App\Events\TrackingLocationUpdated($order, $lat, $lng))->toOthers();
            }
        }

        // Kurye konum güncelleme event'i yayınla
        broadcast(new \App\Events\CourierLocationUpdated($courier))->toOthers();
    }

    /**
     * Teslimat ilerleme yüzdesi
     */
    public function getDeliveryProgress(Order $order): int
    {
        if ($order->status === 'delivered') {
            return 100;
        }

        if ($order->status === 'cancelled') {
            return 0;
        }

        if (!$order->courier || $order->status !== 'on_delivery') {
            return match ($order->status) {
                'pending' => 10,
                'preparing' => 25,
                'ready' => 40,
                default => 0,
            };
        }

        // Kurye yoldaysa, mesafe bazlı hesapla
        $branch = $order->branch;
        if (!$branch || !$order->courier->lat) {
            return 60;
        }

        // Toplam mesafe (şube -> teslimat)
        $totalDistance = $this->calculateDistance(
            $branch->lat ?? 0,
            $branch->lng ?? 0,
            $order->lat,
            $order->lng
        );

        // Kalan mesafe (kurye -> teslimat)
        $remainingDistance = $this->calculateDistance(
            $order->courier->lat,
            $order->courier->lng,
            $order->lat,
            $order->lng
        );

        if ($totalDistance <= 0) {
            return 60;
        }

        // İlerleme: 50% (önceki adımlar) + 50% (teslimat yolu)
        $deliveryProgress = ((($totalDistance - $remainingDistance) / $totalDistance) * 50);

        return min(95, 50 + (int) $deliveryProgress);
    }

    /**
     * Takip verilerini hazırla
     */
    public function getTrackingData(Order $order): array
    {
        $this->calculateETA($order);

        return [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'customer_name' => $order->customer_name,
                'customer_address' => $order->customer_address,
                'lat' => $order->lat,
                'lng' => $order->lng,
                'total' => $order->total,
                'payment_method' => $order->getPaymentMethodLabel(),
                'created_at' => $order->created_at->format('H:i'),
            ],
            'tracking' => [
                'steps' => $order->getTrackingSteps(),
                'current_step' => $order->getCurrentStep(),
                'progress' => $this->getDeliveryProgress($order),
                'estimated_minutes' => $order->getEstimatedMinutesRemaining(),
                'estimated_delivery_at' => $order->estimated_delivery_at?->format('H:i'),
            ],
            'courier' => $order->courier ? [
                'id' => $order->courier->id,
                'name' => $order->courier->name,
                'phone' => $order->courier->phone,
                'photo' => $order->courier->photo_url ?? null,
                'rating' => $order->courier->rating ?? 4.8,
                'location' => $order->getCourierLocation(),
            ] : null,
            'branch' => $order->branch ? [
                'name' => $order->branch->name,
                'address' => $order->branch->address,
                'phone' => $order->branch->phone,
                'lat' => $order->branch->lat,
                'lng' => $order->branch->lng,
            ] : null,
        ];
    }
}
