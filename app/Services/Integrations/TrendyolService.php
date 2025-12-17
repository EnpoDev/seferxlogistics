<?php

namespace App\Services\Integrations;

use App\Models\Order;

class TrendyolService extends BaseIntegrationService
{
    public function getPlatform(): string
    {
        return 'trendyol';
    }

    protected function getPlatformName(): string
    {
        return 'Trendyol Yemek';
    }

    protected function getPlatformDescription(): string
    {
        return 'Trendyol Yemek siparişlerini otomatik olarak alın ve yönetin.';
    }

    protected function getApiBaseUrl(): string
    {
        return 'https://api.trendyol.com/food/v1';
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
            'supplier_id' => [
                'label' => 'Supplier ID',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }

    protected function doTestConnection(array $credentials): bool
    {
        // TODO: Implement actual API connection test
        return !empty($credentials['api_key']) 
            && !empty($credentials['api_secret'])
            && !empty($credentials['supplier_id']);
    }

    protected function getApiHeaders(array $credentials): array
    {
        $auth = base64_encode(($credentials['api_key'] ?? '') . ':' . ($credentials['api_secret'] ?? ''));
        
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
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

        $eventType = $payload['eventType'] ?? null;

        switch ($eventType) {
            case 'ORDER_CREATED':
                // Create new order from payload
                break;
            case 'ORDER_CANCELLED':
                // Handle cancelled order
                break;
        }
    }
}

