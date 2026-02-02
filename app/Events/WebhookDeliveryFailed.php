<?php

namespace App\Events;

use App\Models\RestaurantConnection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookDeliveryFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RestaurantConnection $connection,
        public string $event,
        public int $failureCount,
        public string $errorMessage
    ) {}
}
