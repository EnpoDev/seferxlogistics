<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use Illuminate\Support\Collection;

class RouteOptimizationService
{
    protected const EARTH_RADIUS_KM = 6371;
    protected const AVG_SPEED_KMH = 30; // Ortalama şehir içi hız
    protected const DELIVERY_TIME_MIN = 3; // Teslimat başına ortalama süre

    /**
     * Siparişleri optimize edilmiş sıraya diz (Nearest Neighbor Algoritması)
     */
    public function optimizeRoute(Collection $orders, ?array $startPoint = null): Collection
    {
        if ($orders->count() <= 1) {
            return $orders;
        }

        // Başlangıç noktasını belirle
        $currentPoint = $startPoint ?? [
            'lat' => $orders->first()->lat,
            'lng' => $orders->first()->lng,
        ];

        $unvisited = $orders->values()->all();
        $route = [];

        // Nearest Neighbor - Her adımda en yakın noktayı seç
        while (count($unvisited) > 0) {
            $nearestIndex = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $index => $order) {
                if (!$order->lat || !$order->lng) continue;

                $distance = $this->calculateDistance(
                    $currentPoint['lat'],
                    $currentPoint['lng'],
                    $order->lat,
                    $order->lng
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $nearestOrder = $unvisited[$nearestIndex];
            $route[] = $nearestOrder;
            $currentPoint = [
                'lat' => $nearestOrder->lat,
                'lng' => $nearestOrder->lng,
            ];

            array_splice($unvisited, $nearestIndex, 1);
        }

        // 2-opt iyileştirme uygula
        $route = $this->twoOptImprove($route, $startPoint);

        return collect($route);
    }

    /**
     * 2-opt algoritması ile rotayı iyileştir
     */
    protected function twoOptImprove(array $route, ?array $startPoint = null): array
    {
        if (count($route) < 3) {
            return $route;
        }

        $improved = true;
        $maxIterations = 100;
        $iteration = 0;

        while ($improved && $iteration < $maxIterations) {
            $improved = false;
            $iteration++;

            for ($i = 0; $i < count($route) - 1; $i++) {
                for ($j = $i + 2; $j < count($route); $j++) {
                    // 2-opt swap değerlendirmesi
                    if ($this->shouldSwap($route, $i, $j, $startPoint)) {
                        // Swap işlemi
                        $newRoute = array_merge(
                            array_slice($route, 0, $i + 1),
                            array_reverse(array_slice($route, $i + 1, $j - $i)),
                            array_slice($route, $j + 1)
                        );
                        $route = $newRoute;
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    /**
     * 2-opt swap yapılıp yapılmayacağını kontrol et
     */
    protected function shouldSwap(array $route, int $i, int $j, ?array $startPoint): bool
    {
        $a = $i === 0 && $startPoint ? $startPoint : ['lat' => $route[$i - 1]->lat ?? $route[$i]->lat, 'lng' => $route[$i - 1]->lng ?? $route[$i]->lng];
        $b = ['lat' => $route[$i]->lat, 'lng' => $route[$i]->lng];
        $c = ['lat' => $route[$j - 1]->lat, 'lng' => $route[$j - 1]->lng];
        $d = ['lat' => $route[$j]->lat, 'lng' => $route[$j]->lng];

        $currentDistance = $this->calculateDistance($a['lat'], $a['lng'], $b['lat'], $b['lng']) +
                          $this->calculateDistance($c['lat'], $c['lng'], $d['lat'], $d['lng']);

        $newDistance = $this->calculateDistance($a['lat'], $a['lng'], $c['lat'], $c['lng']) +
                      $this->calculateDistance($b['lat'], $b['lng'], $d['lat'], $d['lng']);

        return $newDistance < $currentDistance;
    }

    /**
     * Rotadaki toplam mesafeyi hesapla
     */
    public function calculateRouteDistance(Collection $orders, ?array $startPoint = null): float
    {
        if ($orders->isEmpty()) {
            return 0;
        }

        $totalDistance = 0;
        $currentPoint = $startPoint ?? ['lat' => $orders->first()->lat, 'lng' => $orders->first()->lng];

        foreach ($orders as $order) {
            if (!$order->lat || !$order->lng) continue;

            $totalDistance += $this->calculateDistance(
                $currentPoint['lat'],
                $currentPoint['lng'],
                $order->lat,
                $order->lng
            );

            $currentPoint = ['lat' => $order->lat, 'lng' => $order->lng];
        }

        return round($totalDistance, 2);
    }

    /**
     * Tahmini teslimat sürelerini hesapla
     */
    public function estimateDeliveryTimes(Collection $orders, ?array $startPoint = null): Collection
    {
        $currentTime = now();
        $currentPoint = $startPoint ?? ['lat' => $orders->first()->lat ?? 0, 'lng' => $orders->first()->lng ?? 0];

        return $orders->map(function ($order, $index) use (&$currentPoint, &$currentTime) {
            if (!$order->lat || !$order->lng) {
                $order->estimated_arrival = null;
                $order->estimated_minutes = null;
                return $order;
            }

            $distance = $this->calculateDistance(
                $currentPoint['lat'],
                $currentPoint['lng'],
                $order->lat,
                $order->lng
            );

            // Seyahat süresi (dakika)
            $travelMinutes = ($distance / self::AVG_SPEED_KMH) * 60;

            // Toplam süre (seyahat + teslimat)
            $totalMinutes = $travelMinutes + self::DELIVERY_TIME_MIN;

            $currentTime = $currentTime->copy()->addMinutes((int) ceil($totalMinutes));

            $order->estimated_arrival = $currentTime->copy();
            $order->estimated_minutes = (int) ceil($totalMinutes);
            $order->distance_from_previous = round($distance, 2);
            $order->sequence = $index + 1;

            $currentPoint = ['lat' => $order->lat, 'lng' => $order->lng];

            return $order;
        });
    }

    /**
     * Kuryenin aktif siparişlerini optimize et
     */
    public function optimizeCourierRoute(Courier $courier): array
    {
        $orders = Order::where('courier_id', $courier->id)
            ->whereIn('status', [Order::STATUS_READY, Order::STATUS_ON_DELIVERY])
            ->get();

        if ($orders->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Optimize edilecek sipariş bulunamadı.',
            ];
        }

        $startPoint = $courier->lat && $courier->lng
            ? ['lat' => $courier->lat, 'lng' => $courier->lng]
            : null;

        $optimizedRoute = $this->optimizeRoute($orders, $startPoint);
        $routeWithTimes = $this->estimateDeliveryTimes($optimizedRoute, $startPoint);
        $totalDistance = $this->calculateRouteDistance($optimizedRoute, $startPoint);

        return [
            'success' => true,
            'route' => $routeWithTimes->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_address' => $order->customer_address,
                'lat' => $order->lat,
                'lng' => $order->lng,
                'sequence' => $order->sequence,
                'estimated_arrival' => $order->estimated_arrival?->format('H:i'),
                'estimated_minutes' => $order->estimated_minutes,
                'distance_from_previous' => $order->distance_from_previous,
            ]),
            'summary' => [
                'total_orders' => $optimizedRoute->count(),
                'total_distance_km' => $totalDistance,
                'estimated_total_time' => $routeWithTimes->sum('estimated_minutes'),
                'start_point' => $startPoint,
            ],
        ];
    }

    /**
     * Siparişleri gruplara ayır (kurye kapasitesine göre)
     */
    public function groupOrdersForCouriers(Collection $orders, int $maxPerCourier = 5): Collection
    {
        // Önce tüm siparişleri tek bir rota olarak optimize et
        $optimizedOrders = $this->optimizeRoute($orders);

        // Sonra kurye kapasitesine göre gruplara ayır
        return $optimizedOrders->chunk($maxPerCourier);
    }

    /**
     * İki nokta arasındaki mesafeyi hesapla (Haversine formülü)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Rota için GeoJSON formatında veri üret (harita çizimi için)
     */
    public function getRouteGeoJson(Collection $orders, ?array $startPoint = null): array
    {
        $coordinates = [];

        if ($startPoint) {
            $coordinates[] = [$startPoint['lng'], $startPoint['lat']];
        }

        foreach ($orders as $order) {
            if ($order->lat && $order->lng) {
                $coordinates[] = [$order->lng, $order->lat];
            }
        }

        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'properties' => [
                'total_orders' => $orders->count(),
                'total_distance' => $this->calculateRouteDistance($orders, $startPoint),
            ],
        ];
    }

    /**
     * Manuel sıralama değişikliği
     */
    public function reorderRoute(array $orderIds): Collection
    {
        return Order::whereIn('id', $orderIds)
            ->get()
            ->sortBy(function ($order) use ($orderIds) {
                return array_search($order->id, $orderIds);
            })
            ->values();
    }
}
