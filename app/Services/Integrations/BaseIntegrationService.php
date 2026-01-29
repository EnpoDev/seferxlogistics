<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
    abstract public function getPlatformName(): string;

    /**
     * Get the platform description
     */
    abstract public function getPlatformDescription(): string;

    /**
     * Get the API base URL
     */
    abstract protected function getApiBaseUrl(): string;

    /**
     * Get required credentials fields
     */
    abstract public function getRequiredCredentials(): array;

    /**
     * Map platform order status to internal status
     */
    abstract protected function mapOrderStatus(string $platformStatus): string;

    /**
     * Map internal status to platform status
     */
    abstract protected function mapToPlatformStatus(string $internalStatus): string;

    /**
     * Parse platform order data to internal format
     */
    abstract protected function parseOrderData(array $platformOrder): array;

    /**
     * Test connection with credentials
     */
    public function testConnection(array $credentials): bool
    {
        try {
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
            $platformOrders = $this->doFetchOrders();
            $orders = [];

            foreach ($platformOrders as $platformOrder) {
                $order = $this->createOrUpdateOrder($platformOrder);
                if ($order) {
                    $orders[] = $order;
                }
            }

            // Update last sync time
            $this->integration->update(['last_sync_at' => now()]);

            return $orders;
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Fetch orders failed: " . $e->getMessage());
            $this->integration->markAsError($e->getMessage());
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
     * Create or update an order from platform data
     */
    protected function createOrUpdateOrder(array $platformOrder): ?Order
    {
        try {
            $orderData = $this->parseOrderData($platformOrder);
            
            // Check if order already exists
            $existingOrder = Order::where('order_number', $orderData['order_number'])->first();
            
            if ($existingOrder) {
                // Update existing order status if changed
                if ($existingOrder->status !== $orderData['status']) {
                    $existingOrder->update(['status' => $orderData['status']]);
                }
                return $existingOrder;
            }

            // Find or create customer
            $customer = null;
            if (!empty($orderData['customer_phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $orderData['customer_phone']],
                    ['name' => $orderData['customer_name'] ?? 'Platform Müşterisi']
                );
            }

            // Create new order
            return DB::transaction(function () use ($orderData, $customer) {
                $order = Order::create([
                    'order_number' => $orderData['order_number'],
                    'customer_id' => $customer?->id,
                    'customer_name' => $orderData['customer_name'] ?? '',
                    'customer_phone' => $orderData['customer_phone'] ?? '',
                    'customer_address' => $orderData['customer_address'] ?? '',
                    'lat' => $orderData['lat'] ?? null,
                    'lng' => $orderData['lng'] ?? null,
                    'subtotal' => $orderData['subtotal'] ?? 0,
                    'delivery_fee' => $orderData['delivery_fee'] ?? 0,
                    'total' => $orderData['total'] ?? 0,
                    'payment_method' => $orderData['payment_method'] ?? Order::PAYMENT_ONLINE,
                    'is_paid' => $orderData['is_paid'] ?? true,
                    'status' => $orderData['status'] ?? Order::STATUS_PENDING,
                    'notes' => $orderData['notes'] ?? null,
                ]);

                // Create order items if provided
                if (!empty($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        $order->items()->create([
                            'product_name' => $item['name'] ?? '',
                            'quantity' => $item['quantity'] ?? 1,
                            'price' => $item['price'] ?? 0,
                            'total' => ($item['quantity'] ?? 1) * ($item['price'] ?? 0),
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }

                Log::info("[{$this->getPlatform()}] Order created: {$order->order_number}");
                
                return $order;
            });
        } catch (\Exception $e) {
            Log::error("[{$this->getPlatform()}] Failed to create order: " . $e->getMessage(), $platformOrder);
            return null;
        }
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
            $platformStatus = $this->mapToPlatformStatus($status);
            $result = $this->doUpdateOrderStatus($order, $platformStatus);
            
            if ($result) {
                Log::info("[{$this->getPlatform()}] Order status updated: {$order->order_number} -> {$status}");
            }
            
            return $result;
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
            $result = $this->doSyncMenu();
            
            if ($result) {
                $this->integration->update(['last_sync_at' => now()]);
                Log::info("[{$this->getPlatform()}] Menu synced successfully");
            }
            
            return $result;
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
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        Log::debug("[{$this->getPlatform()}] API Request: {$method} {$url}");

        $response = Http::withHeaders($this->getApiHeaders($credentials))
            ->timeout(30)
            ->$method($url, $data);

        if ($response->failed()) {
            $error = "API request failed [{$response->status()}]: " . $response->body();
            Log::error("[{$this->getPlatform()}] {$error}");
            throw new \Exception($error);
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

    /**
     * Validate required credentials
     */
    protected function validateCredentials(array $credentials): bool
    {
        $required = $this->getRequiredCredentials();
        
        foreach ($required as $key => $config) {
            if ($config['required'] && empty($credentials[$key])) {
                return false;
            }
        }
        
        return true;
    }
}

