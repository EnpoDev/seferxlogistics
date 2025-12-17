<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Order;

interface IntegrationInterface
{
    /**
     * Get the platform identifier
     */
    public function getPlatform(): string;

    /**
     * Test the connection with provided credentials
     */
    public function testConnection(array $credentials): bool;

    /**
     * Connect to the platform
     */
    public function connect(array $credentials): bool;

    /**
     * Disconnect from the platform
     */
    public function disconnect(): bool;

    /**
     * Fetch new orders from the platform
     */
    public function fetchOrders(): array;

    /**
     * Update order status on the platform
     */
    public function updateOrderStatus(Order $order, string $status): bool;

    /**
     * Sync menu/products with the platform
     */
    public function syncMenu(): bool;

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $payload): void;

    /**
     * Get the integration model
     */
    public function getIntegration(): ?Integration;
}

