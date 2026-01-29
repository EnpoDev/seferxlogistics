<?php

namespace App\Listeners;

use App\Events\CourierLocationUpdated;
use App\Events\CourierStatusChanged;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\PoolOrderAdded;
use App\Events\PoolOrderAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateAuditLogListener implements ShouldQueue
{
    public function handle(object $event): void
    {
        $logData = $this->extractLogData($event);

        if ($logData) {
            Log::channel('audit')->info($logData['message'], $logData['context']);
        }
    }

    protected function extractLogData(object $event): ?array
    {
        return match (true) {
            $event instanceof OrderCreated => [
                'message' => 'Order created',
                'context' => [
                    'event_type' => 'order_created',
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                    'branch_id' => $event->order->branch_id,
                    'customer_id' => $event->order->customer_id,
                    'total' => $event->order->total,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $event instanceof OrderStatusUpdated => [
                'message' => 'Order status changed',
                'context' => [
                    'event_type' => 'order_status_updated',
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->order->status,
                    'courier_id' => $event->order->courier_id,
                    'customer_id' => $event->order->customer_id,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $event instanceof PoolOrderAdded => [
                'message' => 'Order added to pool',
                'context' => [
                    'event_type' => 'pool_order_added',
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                    'branch_id' => $event->order->branch_id,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $event instanceof PoolOrderAssigned => [
                'message' => 'Pool order assigned to courier',
                'context' => [
                    'event_type' => 'pool_order_assigned',
                    'order_id' => $event->order->id,
                    'order_number' => $event->order->order_number,
                    'courier_id' => $event->courier->id,
                    'courier_name' => $event->courier->name,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $event instanceof CourierLocationUpdated => [
                'message' => 'Courier location updated',
                'context' => [
                    'event_type' => 'courier_location_updated',
                    'courier_id' => $event->courier->id ?? null,
                    'lat' => $event->courier->lat ?? null,
                    'lng' => $event->courier->lng ?? null,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            $event instanceof CourierStatusChanged => [
                'message' => 'Courier status changed',
                'context' => [
                    'event_type' => 'courier_status_changed',
                    'courier_id' => $event->courier->id ?? null,
                    'status' => $event->courier->status ?? null,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            default => null,
        };
    }
}
