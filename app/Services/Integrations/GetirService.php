<?php

namespace App\Services\Integrations;

use App\Models\Order;

class GetirService extends BaseIntegrationService
{
    public function getPlatform(): string
    {
        return 'getir';
    }

    protected function getPlatformName(): string
    {
        return 'Getir Yemek';
    }

    protected function getPlatformDescription(): string
    {
        return 'Getir Yemek siparişlerini otomatik olarak alın ve yönetin.';
    }

    protected function getApiBaseUrl(): string
    {
        return 'https://api.getir.com/restaurant/v1';
    }

    public function getRequiredCredentials(): array
    {
        return [
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
            ],
            'client_secret' => [
                'label' => 'Client Secret',
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
        return !empty($credentials['client_id']) 
            && !empty($credentials['client_secret'])
            && !empty($credentials['restaurant_id']);
    }

    protected function getApiHeaders(array $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
        ];
    }

    protected function doFetchOrders(): array
    {
        // TODO: Implement actual API call
        return [];
    }

    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        // TODO: Implement actual API call
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

        $eventType = $payload['type'] ?? null;

        switch ($eventType) {
            case 'NEW_ORDER':
                // Create new order from payload
                break;
            case 'CANCEL_ORDER':
                // Handle cancelled order
                break;
        }
    }
}

