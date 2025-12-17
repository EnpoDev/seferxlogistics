<?php

namespace App\Services\Integrations;

use App\Models\Order;

class YemeksepetiService extends BaseIntegrationService
{
    public function getPlatform(): string
    {
        return 'yemeksepeti';
    }

    protected function getPlatformName(): string
    {
        return 'Yemeksepeti';
    }

    protected function getPlatformDescription(): string
    {
        return 'Yemeksepeti siparişlerini otomatik olarak alın ve yönetin.';
    }

    protected function getApiBaseUrl(): string
    {
        return 'https://api.yemeksepeti.com/v1';
    }

    public function getRequiredCredentials(): array
    {
        return [
            'api_key' => [
                'label' => 'API Anahtarı',
                'type' => 'text',
                'required' => true,
            ],
            'api_secret' => [
                'label' => 'API Secret',
                'type' => 'password',
                'required' => true,
            ],
            'restaurant_id' => [
                'label' => 'Restoran ID',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }

    protected function doTestConnection(array $credentials): bool
    {
        // TODO: Implement actual API connection test
        // For now, just validate credentials are provided
        return !empty($credentials['api_key']) 
            && !empty($credentials['api_secret'])
            && !empty($credentials['restaurant_id']);
    }

    protected function getApiHeaders(array $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $credentials['api_key'] ?? '',
            'X-API-Secret' => $credentials['api_secret'] ?? '',
        ];
    }

    protected function doFetchOrders(): array
    {
        // TODO: Implement actual API call
        // $response = $this->apiRequest('get', '/orders/pending');
        return [];
    }

    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        // TODO: Implement actual API call
        // Map our status to Yemeksepeti status
        $statusMap = [
            'pending' => 'pending',
            'preparing' => 'accepted',
            'ready' => 'ready',
            'on_delivery' => 'delivering',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
        ];

        return true;
    }

    protected function doSyncMenu(): bool
    {
        // TODO: Implement menu sync
        return true;
    }

    public function handleWebhook(array $payload): void
    {
        parent::handleWebhook($payload);

        $eventType = $payload['event_type'] ?? null;

        switch ($eventType) {
            case 'order.new':
                // Create new order from payload
                break;
            case 'order.cancelled':
                // Handle cancelled order
                break;
        }
    }
}

