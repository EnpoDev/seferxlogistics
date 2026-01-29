<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AIOrderDistributionService
{
    // Ağırlık faktörleri (toplam 100)
    private const WEIGHT_DISTANCE = 35;      // Mesafe önceliği
    private const WEIGHT_WORKLOAD = 25;      // İş yükü
    private const WEIGHT_PERFORMANCE = 20;   // Performans (teslimat süresi)
    private const WEIGHT_ZONE = 15;          // Bölge uyumu
    private const WEIGHT_AVAILABILITY = 5;   // Müsaitlik süresi

    // Limitler
    private const MAX_DISTANCE_KM = 10;      // Maksimum mesafe
    private const MAX_ACTIVE_ORDERS = 5;     // Maksimum aktif sipariş

    /**
     * Sipariş için en uygun kuryeyi bul
     */
    public function findBestCourier(Order $order): ?Courier
    {
        $availableCouriers = $this->getAvailableCouriers($order);

        if ($availableCouriers->isEmpty()) {
            Log::info("AI Dağıtım: Sipariş #{$order->id} için uygun kurye bulunamadı");
            return null;
        }

        // Her kurye için skor hesapla
        $scoredCouriers = $availableCouriers->map(function ($courier) use ($order) {
            return [
                'courier' => $courier,
                'score' => $this->calculateCourierScore($courier, $order),
                'factors' => $this->getScoreFactors($courier, $order),
            ];
        })->sortByDesc('score');

        $bestMatch = $scoredCouriers->first();

        Log::info("AI Dağıtım: Sipariş #{$order->id} için en iyi kurye: {$bestMatch['courier']->name} (Skor: {$bestMatch['score']})");

        return $bestMatch['courier'];
    }

    /**
     * Müsait kuryeleri getir
     */
    private function getAvailableCouriers(Order $order): Collection
    {
        return Courier::query()
            ->where(function ($query) {
                // Müsait veya meşgul ama kapasitesi olan kuryeler
                $query->where('status', Courier::STATUS_AVAILABLE)
                    ->orWhere(function ($q) {
                        $q->where('status', Courier::STATUS_BUSY)
                            ->where('active_orders_count', '<', self::MAX_ACTIVE_ORDERS);
                    });
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->filter(function ($courier) {
                return $courier->isOnShift();
            });
    }

    /**
     * Kurye skorunu hesapla (0-100)
     */
    private function calculateCourierScore(Courier $courier, Order $order): float
    {
        $distanceScore = $this->calculateDistanceScore($courier, $order);
        $workloadScore = $this->calculateWorkloadScore($courier);
        $performanceScore = $this->calculatePerformanceScore($courier);
        $zoneScore = $this->calculateZoneScore($courier, $order);
        $availabilityScore = $this->calculateAvailabilityScore($courier);

        return round(
            ($distanceScore * self::WEIGHT_DISTANCE / 100) +
            ($workloadScore * self::WEIGHT_WORKLOAD / 100) +
            ($performanceScore * self::WEIGHT_PERFORMANCE / 100) +
            ($zoneScore * self::WEIGHT_ZONE / 100) +
            ($availabilityScore * self::WEIGHT_AVAILABILITY / 100),
            2
        );
    }

    /**
     * Mesafe skoru (0-100, yakın = yüksek)
     */
    private function calculateDistanceScore(Courier $courier, Order $order): float
    {
        // Kurye -> Şube/Restoran mesafesi
        $branch = $order->branch;
        $pickupLat = $branch?->lat ?? $order->restaurant?->lat ?? $order->lat;
        $pickupLng = $branch?->lng ?? $order->restaurant?->lng ?? $order->lng;

        $distanceToPickup = $this->calculateDistance(
            $courier->lat,
            $courier->lng,
            $pickupLat,
            $pickupLng
        );

        // Normalize: 0km = 100, MAX_DISTANCE_KM = 0
        if ($distanceToPickup >= self::MAX_DISTANCE_KM) {
            return 0;
        }

        return ((self::MAX_DISTANCE_KM - $distanceToPickup) / self::MAX_DISTANCE_KM) * 100;
    }

    /**
     * İş yükü skoru (0-100, az sipariş = yüksek)
     */
    private function calculateWorkloadScore(Courier $courier): float
    {
        $activeOrders = $courier->active_orders_count ?? 0;

        if ($activeOrders >= self::MAX_ACTIVE_ORDERS) {
            return 0;
        }

        return ((self::MAX_ACTIVE_ORDERS - $activeOrders) / self::MAX_ACTIVE_ORDERS) * 100;
    }

    /**
     * Performans skoru (0-100, hızlı teslimat = yüksek)
     */
    private function calculatePerformanceScore(Courier $courier): float
    {
        $avgDeliveryTime = $courier->average_delivery_time ?? 30;
        $totalDeliveries = $courier->total_deliveries ?? 0;

        // Yeni kuryeler için nötr skor
        if ($totalDeliveries < 10) {
            return 50;
        }

        // Ortalama teslimat süresi bazlı skor
        // 15 dakika = 100, 45 dakika = 0
        $targetTime = 15;
        $maxTime = 45;

        if ($avgDeliveryTime <= $targetTime) {
            return 100;
        }

        if ($avgDeliveryTime >= $maxTime) {
            return 0;
        }

        return (($maxTime - $avgDeliveryTime) / ($maxTime - $targetTime)) * 100;
    }

    /**
     * Bölge uyumu skoru (0-100)
     */
    private function calculateZoneScore(Courier $courier, Order $order): float
    {
        // Kurye bölgeleri kontrolü
        $courierZones = $courier->zones()->pluck('zones.id')->toArray();

        if (empty($courierZones)) {
            // Bölge ataması yoksa nötr skor
            return 50;
        }

        // Siparişin bölgesi kurye bölgelerinde mi?
        // Not: Sipariş koordinatları ile bölge çokgenlerini karşılaştırmalı
        // Şimdilik basit yaklaşım: Şubenin bölgesi
        $orderZone = $order->branch?->zones()->first();

        if ($orderZone && in_array($orderZone->id, $courierZones)) {
            return 100;
        }

        return 30;
    }

    /**
     * Müsaitlik skoru (0-100)
     */
    private function calculateAvailabilityScore(Courier $courier): float
    {
        // Duruma göre skor
        return match ($courier->status) {
            Courier::STATUS_AVAILABLE => 100,
            Courier::STATUS_BUSY => 50,
            default => 0,
        };
    }

    /**
     * Skor faktörlerini detaylı getir (debug/analiz için)
     */
    private function getScoreFactors(Courier $courier, Order $order): array
    {
        return [
            'distance' => [
                'score' => round($this->calculateDistanceScore($courier, $order), 1),
                'weight' => self::WEIGHT_DISTANCE,
            ],
            'workload' => [
                'score' => round($this->calculateWorkloadScore($courier), 1),
                'weight' => self::WEIGHT_WORKLOAD,
                'active_orders' => $courier->active_orders_count ?? 0,
            ],
            'performance' => [
                'score' => round($this->calculatePerformanceScore($courier), 1),
                'weight' => self::WEIGHT_PERFORMANCE,
                'avg_time' => $courier->average_delivery_time ?? 'N/A',
            ],
            'zone' => [
                'score' => round($this->calculateZoneScore($courier, $order), 1),
                'weight' => self::WEIGHT_ZONE,
            ],
            'availability' => [
                'score' => round($this->calculateAvailabilityScore($courier), 1),
                'weight' => self::WEIGHT_AVAILABILITY,
                'status' => $courier->status,
            ],
        ];
    }

    /**
     * Haversine mesafe hesaplama
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Toplu sipariş dağıtımı
     */
    public function distributePendingOrders(): array
    {
        $pendingOrders = Order::where('status', 'ready')
            ->whereNull('courier_id')
            ->get();

        $results = [
            'total' => $pendingOrders->count(),
            'assigned' => 0,
            'failed' => 0,
            'assignments' => [],
        ];

        foreach ($pendingOrders as $order) {
            $bestCourier = $this->findBestCourier($order);

            if ($bestCourier) {
                $order->assignCourier($bestCourier);
                $bestCourier->incrementActiveOrders();

                $results['assigned']++;
                $results['assignments'][] = [
                    'order_id' => $order->id,
                    'courier_id' => $bestCourier->id,
                    'courier_name' => $bestCourier->name,
                ];
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Sipariş için en iyi N kuryeyi öneri listesi olarak getir
     */
    public function getSuggestedCouriers(Order $order, int $limit = 5): Collection
    {
        $availableCouriers = $this->getAvailableCouriers($order);

        return $availableCouriers->map(function ($courier) use ($order) {
            return [
                'courier' => $courier,
                'score' => $this->calculateCourierScore($courier, $order),
                'factors' => $this->getScoreFactors($courier, $order),
                'distance_km' => $this->calculateDistance(
                    $courier->lat,
                    $courier->lng,
                    $order->branch?->lat ?? $order->lat,
                    $order->branch?->lng ?? $order->lng
                ),
            ];
        })->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Dağıtım istatistiklerini getir
     */
    public function getDistributionStats(): array
    {
        $today = now()->startOfDay();

        $todayOrders = Order::whereDate('created_at', $today)
            ->whereNotNull('courier_id')
            ->get();

        $avgAssignmentTime = $todayOrders->avg(function ($order) {
            if ($order->created_at && $order->courier_assigned_at) {
                return $order->created_at->diffInMinutes($order->courier_assigned_at);
            }
            return null;
        });

        return [
            'today_assigned' => $todayOrders->count(),
            'avg_assignment_time_minutes' => round($avgAssignmentTime ?? 0, 1),
            'available_couriers' => Courier::where('status', Courier::STATUS_AVAILABLE)->count(),
            'busy_couriers' => Courier::where('status', Courier::STATUS_BUSY)->count(),
        ];
    }
}
