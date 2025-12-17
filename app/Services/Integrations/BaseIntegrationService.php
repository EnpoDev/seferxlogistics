<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseIntegrationService implements IntegrationInterface
{
    protected ?Integration $integration = null;

    public function __construct()
    {
        $this->integration = Integration::where('platform', $this->getPlatform())->first();
    }

    /**
     * Get the integration model
     */
    public function getIntegration(): ?Integration
    {
        return $this->integration;
    }

    /**
     * Get or create integration record
     */
    protected function getOrCreateIntegration(array $data = []): Integration
    {
        return Integration::firstOrCreate(
            ['platform' => $this->getPlatform()],
            array_merge([
                'name' => $this->getPlatformName(),
                'description' => $this->getPlatformDescription(),
                'is_active' => false,
                'status' => Integration::STATUS_INACTIVE,
            ], $data)
        );
    }

    /**
     * Get the platform display name
     */
    abstract protected function getPlatformName(): string;

    /**
     * Get the platform description
     */
    abstract protected function getPlatformDescription(): string;

    /**
     * Get the API base URL
     */
    abstract protected function getApiBaseUrl(): string;

    /**
     * Get required credentials fields
     */
    abstract public function getRequiredCredentials(): array;

    /**
     * Test connection with credentials
     */
    public function testConnection(array $credentials): bool
    {
        try {
            // Override in child classes for platform-specific testing
            return $this->doTestConnection($credentials);
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform the actual connection test
     */
    protected function doTestConnection(array $credentials): bool
    {
        // Override in child classes
        return false;
    }

    /**
     * Connect to the platform
     */
    public function connect(array $credentials): bool
    {
        try {
            $integration = $this->getOrCreateIntegration();

            if (!$this->testConnection($credentials)) {
                $integration->markAsError('Bağlantı testi başarısız oldu.');
                return false;
            }

            $integration->update([
                'credentials' => $credentials,
                'is_active' => true,
                'status' => Integration::STATUS_CONNECTED,
                'is_connected' => true,
                'last_sync_at' => now(),
                'error_message' => null,
            ]);

            $integration->generateWebhookUrl();
            $this->integration = $integration;

            return true;
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Connection failed: " . $e->getMessage());
            
            if ($this->integration) {
                $this->integration->markAsError($e->getMessage());
            }
            
            return false;
        }
    }

    /**
     * Disconnect from the platform
     */
    public function disconnect(): bool
    {
        if ($this->integration) {
            $this->integration->disconnect();
            return true;
        }
        return false;
    }

    /**
     * Fetch orders from platform
     */
    public function fetchOrders(): array
    {
        if (!$this->integration || !$this->integration->is_connected) {
            return [];
        }

        try {
            return $this->doFetchOrders();
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Fetch orders failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Perform the actual order fetch
     */
    protected function doFetchOrders(): array
    {
        // Override in child classes
        return [];
    }

    /**
     * Update order status on platform
     */
    public function updateOrderStatus(Order $order, string $status): bool
    {
        if (!$this->integration || !$this->integration->is_connected) {
            return false;
        }

        try {
            return $this->doUpdateOrderStatus($order, $status);
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Update order status failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform the actual order status update
     */
    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        // Override in child classes
        return false;
    }

    /**
     * Sync menu with platform
     */
    public function syncMenu(): bool
    {
        if (!$this->integration || !$this->integration->is_connected) {
            return false;
        }

        try {
            return $this->doSyncMenu();
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Menu sync failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform the actual menu sync
     */
    protected function doSyncMenu(): bool
    {
        // Override in child classes
        return false;
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $payload): void
    {
        Log::info("[{$this->getPlatform()}] Webhook received", $payload);
        // Override in child classes
    }

    /**
     * Make an authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $credentials = $this->integration?->credentials ?? [];
        $baseUrl = $this->getApiBaseUrl();

        $response = Http::withHeaders($this->getApiHeaders($credentials))
            ->$method("{$baseUrl}{$endpoint}", $data);

        if ($response->failed()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Get API headers for requests
     */
    protected function getApiHeaders(array $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}

