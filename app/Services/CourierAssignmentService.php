<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Collection;

class CourierAssignmentService
{
    /**
     * Find the best courier for an order based on:
     * 1. Availability (status: available)
     * 2. Currently on shift
     * 3. Lowest active order count
     * 4. Closest distance to delivery location
     */
    public function findBestCourier(?float $lat = null, ?float $lng = null): ?Courier
    {
        $couriers = $this->getAvailableCouriers();

        if ($couriers->isEmpty()) {
            return null;
        }

        // Score each courier
        $scoredCouriers = $couriers->map(function ($courier) use ($lat, $lng) {
            $score = $this->calculateCourierScore($courier, $lat, $lng);
            return [
                'courier' => $courier,
                'score' => $score,
            ];
        });

        // Sort by score (higher is better) and return the best one
        $best = $scoredCouriers->sortByDesc('score')->first();

        return $best ? $best['courier'] : null;
    }

    /**
     * Get all available couriers who are on shift
     */
    public function getAvailableCouriers(): Collection
    {
        return Courier::where('status', Courier::STATUS_AVAILABLE)
            ->orWhere(function ($query) {
                $query->where('status', Courier::STATUS_BUSY)
                      ->where('active_orders_count', '<', 3); // Can still take orders if less than 3 active
            })
            ->get()
            ->filter(function ($courier) {
                return $courier->isOnShift();
            });
    }

    /**
     * Calculate a score for a courier based on various factors
     */
    protected function calculateCourierScore(Courier $courier, ?float $lat = null, ?float $lng = null): float
    {
        $score = 100;

        // Factor 1: Active orders count (less is better)
        // Deduct points for each active order
        $score -= $courier->active_orders_count * 20;

        // Factor 2: Distance to delivery location (closer is better)
        if ($lat !== null && $lng !== null && $courier->lat && $courier->lng) {
            $distance = $courier->calculateDistanceTo($lat, $lng);
            // Deduct 1 point per 100 meters
            $score -= ($distance / 100);
        }

        // Factor 3: Average delivery time (faster is better)
        if ($courier->average_delivery_time) {
            // Bonus points for faster couriers
            if ($courier->average_delivery_time < 30) {
                $score += 10;
            } elseif ($courier->average_delivery_time < 45) {
                $score += 5;
            }
        }

        // Factor 4: Total deliveries (more experienced is slightly better)
        if ($courier->total_deliveries > 100) {
            $score += 5;
        } elseif ($courier->total_deliveries > 50) {
            $score += 3;
        }

        // Factor 5: Notification enabled (prefer couriers who can receive notifications)
        if ($courier->notification_enabled) {
            $score += 5;
        }

        return max(0, $score); // Ensure score doesn't go negative
    }

    /**
     * Assign a courier to an order
     */
    public function assignCourierToOrder(Order $order, ?int $courierId = null): bool
    {
        $courier = null;

        if ($courierId) {
            // Manual assignment
            $courier = Courier::find($courierId);
            
            if (!$courier) {
                return false;
            }
        } else {
            // Auto assignment
            $courier = $this->findBestCourier($order->lat, $order->lng);
            
            if (!$courier) {
                return false;
            }
        }

        // Update the order
        $order->update([
            'courier_id' => $courier->id,
        ]);

        // Update courier active orders count
        $courier->incrementActiveOrders();

        return true;
    }

    /**
     * Reassign a courier to an order
     */
    public function reassignCourier(Order $order, int $newCourierId): bool
    {
        $oldCourierId = $order->courier_id;
        $newCourier = Courier::find($newCourierId);

        if (!$newCourier) {
            return false;
        }

        // Update the order
        $order->update([
            'courier_id' => $newCourierId,
        ]);

        // Update old courier's active orders count
        if ($oldCourierId) {
            $oldCourier = Courier::find($oldCourierId);
            $oldCourier?->decrementActiveOrders();
        }

        // Update new courier's active orders count
        $newCourier->incrementActiveOrders();

        return true;
    }

    /**
     * Get couriers who can receive notifications (on shift and enabled)
     */
    public function getCouriersForNotification(): Collection
    {
        return Courier::canReceiveNotifications()->get();
    }

    /**
     * Check if a courier is within their shift hours
     */
    public function isCourierOnShift(Courier $courier): bool
    {
        return $courier->isOnShift();
    }

    /**
     * Check if a courier can receive notifications
     */
    public function canCourierReceiveNotification(Courier $courier): bool
    {
        return $courier->canReceiveNotification();
    }

    /**
     * Get courier workload statistics
     */
    public function getCourierWorkloadStats(): array
    {
        $couriers = Courier::all();
        
        return [
            'total_couriers' => $couriers->count(),
            'available_couriers' => $couriers->where('status', Courier::STATUS_AVAILABLE)->count(),
            'busy_couriers' => $couriers->where('status', Courier::STATUS_BUSY)->count(),
            'offline_couriers' => $couriers->where('status', Courier::STATUS_OFFLINE)->count(),
            'on_break_couriers' => $couriers->where('status', Courier::STATUS_ON_BREAK)->count(),
            'on_shift_couriers' => $couriers->filter(fn($c) => $c->isOnShift())->count(),
            'total_active_orders' => $couriers->sum('active_orders_count'),
            'average_orders_per_courier' => $couriers->where('status', '!=', Courier::STATUS_OFFLINE)->count() > 0
                ? round($couriers->sum('active_orders_count') / $couriers->where('status', '!=', Courier::STATUS_OFFLINE)->count(), 2)
                : 0,
        ];
    }
}

