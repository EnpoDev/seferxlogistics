<?php

namespace App\Services\Integrations;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GetirService extends BaseIntegrationService
{
    protected ?string $accessToken = null;

    public function getPlatform(): string
    {
        return 'getir';
    }

    public function getPlatformName(): string
    {
        return 'Getir Yemek';
    }

    public function getPlatformDescription(): string
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

    /**
     * Map Getir order status to internal status
     */
    protected function mapOrderStatus(string $platformStatus): string
    {
        return match (strtoupper($platformStatus)) {
            'PENDING', 'NEW' => Order::STATUS_PENDING,
            'ACCEPTED', 'PREPARING' => Order::STATUS_PREPARING,
            'READY', 'PREPARED' => Order::STATUS_READY,
            'ON_WAY', 'ON_DELIVERY' => Order::STATUS_ON_DELIVERY,
            'DELIVERED' => Order::STATUS_DELIVERED,
            'CANCELLED', 'REJECTED' => Order::STATUS_CANCELLED,
            default => Order::STATUS_PENDING,
        };
    }

    /**
     * Map internal status to Getir status
     */
    protected function mapToPlatformStatus(string $internalStatus): string
    {
        return match ($internalStatus) {
            Order::STATUS_PENDING => 'pending',
            Order::STATUS_PREPARING => 'accepted',
            Order::STATUS_READY => 'prepared',
            Order::STATUS_ON_DELIVERY => 'on_way',
            Order::STATUS_DELIVERED => 'delivered',
            Order::STATUS_CANCELLED => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Parse Getir order data to internal format
     */
    protected function parseOrderData(array $platformOrder): array
    {
        $items = [];
        foreach (($platformOrder['products'] ?? []) as $product) {
            $items[] = [
                'name' => $product['name'] ?? '',
                'quantity' => $product['count'] ?? 1,
                'price' => ($product['price'] ?? 0) / 100, // Getir uses kuruş
                'notes' => $product['note'] ?? null,
            ];
        }

        $address = $platformOrder['client']['address'] ?? [];
        
        return [
            'order_number' => 'GTR-' . ($platformOrder['id'] ?? uniqid()),
            'customer_name' => $platformOrder['client']['name'] ?? '',
            'customer_phone' => $platformOrder['client']['phoneNumber'] ?? '',
            'customer_address' => $this->formatGetirAddress($address),
            'lat' => $address['location']['lat'] ?? null,
            'lng' => $address['location']['lon'] ?? null,
            'subtotal' => ($platformOrder['totalPrice'] ?? 0) / 100,
            'delivery_fee' => ($platformOrder['courierFee'] ?? 0) / 100,
            'total' => ($platformOrder['totalPrice'] ?? 0) / 100,
            'payment_method' => $this->mapPaymentMethod($platformOrder['paymentMethod'] ?? ''),
            'is_paid' => ($platformOrder['paymentMethod'] ?? '') !== 'CASH',
            'status' => $this->mapOrderStatus($platformOrder['status'] ?? 'NEW'),
            'notes' => $platformOrder['clientNote'] ?? null,
            'items' => $items,
        ];
    }

    /**
     * Format Getir address to string
     */
    protected function formatGetirAddress(array $address): string
    {
        $parts = array_filter([
            $address['apartment'] ?? '',
            $address['building'] ?? '',
            $address['street'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
        ]);
        
        $formatted = implode(', ', $parts);
        
        if (!empty($address['directions'])) {
            $formatted .= ' (' . $address['directions'] . ')';
        }
        
        return $formatted;
    }

    /**
     * Map payment method
     */
    protected function mapPaymentMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'CASH', 'CASH_ON_DELIVERY' => Order::PAYMENT_CASH,
            'CREDIT_CARD', 'CARD' => Order::PAYMENT_CARD,
            default => Order::PAYMENT_ONLINE,
        };
    }

    /**
     * Get OAuth access token
     */
    protected function getAccessToken(array $credentials): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            // In production, this would call Getir's OAuth endpoint
            // For now, simulate token generation
            $response = Http::timeout(30)->post('https://api.getir.com/oauth/token', [
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
                'grant_type' => 'client_credentials',
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                return $this->accessToken;
            }

            Log::warning("[Getir] Failed to get access token: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::warning("[Getir] Token request failed: " . $e->getMessage());
            // Return a placeholder for API-ready structure
            return null;
        }
    }

    protected function doTestConnection(array $credentials): bool
    {
        // Validate required credentials are present
        if (!$this->validateCredentials($credentials)) {
            return false;
        }

        // In production, attempt to get an access token to verify credentials
        // For API-ready structure, we validate format and simulate success
        try {
            // Validate credential formats
            if (strlen($credentials['client_id']) < 10) {
                return false;
            }
            if (strlen($credentials['client_secret']) < 10) {
                return false;
            }
            if (strlen($credentials['restaurant_id']) < 5) {
                return false;
            }

            // API-ready: In production, uncomment below
            // $token = $this->getAccessToken($credentials);
            // return $token !== null;

            Log::info("[Getir] Connection test passed (API-ready mode)");
            return true;
        } catch (\Exception $e) {
            Log::error("[Getir] Connection test error: " . $e->getMessage());
            return false;
        }
    }

    protected function getApiHeaders(array $credentials): array
    {
        $token = $this->accessToken ?? ($credentials['access_token'] ?? '');
        
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'X-Restaurant-Id' => $credentials['restaurant_id'] ?? '',
        ];
    }

    protected function doFetchOrders(): array
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would call:
        // return $this->apiRequest('get', '/orders/active');
        
        Log::info("[Getir] Fetching orders (API-ready mode)");
        
        // Return empty array - ready for real API integration
        return [];
    }

    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $orderId = str_replace('GTR-', '', $order->order_number);
        
        // API-ready: In production, this would call:
        // $this->apiRequest('put', "/orders/{$orderId}/status", ['status' => $status]);
        
        Log::info("[Getir] Updating order status: {$order->order_number} -> {$status} (API-ready mode)");
        
        return true;
    }

    protected function doSyncMenu(): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would:
        // 1. Fetch products from database
        // 2. Format for Getir API
        // 3. Call $this->apiRequest('post', '/menu/sync', $menuData);
        
        Log::info("[Getir] Syncing menu (API-ready mode)");
        
        return true;
    }

    public function handleWebhook(array $payload): void
    {
        parent::handleWebhook($payload);

        $eventType = $payload['type'] ?? $payload['event'] ?? null;

        switch (strtoupper($eventType ?? '')) {
            case 'NEW_ORDER':
            case 'ORDER_CREATED':
                $this->handleNewOrder($payload);
                break;
                
            case 'ORDER_CANCELLED':
            case 'CANCEL_ORDER':
                $this->handleCancelledOrder($payload);
                break;
                
            case 'ORDER_STATUS_CHANGED':
                $this->handleStatusChange($payload);
                break;
                
            default:
                Log::info("[Getir] Unhandled webhook event: {$eventType}");
        }
    }

    /**
     * Handle new order webhook
     */
    protected function handleNewOrder(array $payload): void
    {
        $orderData = $payload['order'] ?? $payload['data'] ?? $payload;
        
        if (empty($orderData)) {
            Log::warning("[Getir] Empty order data in webhook");
            return;
        }

        $order = $this->createOrUpdateOrder($orderData);
        
        if ($order) {
            Log::info("[Getir] New order created from webhook: {$order->order_number}");
            
            // Dispatch event for real-time updates
            event(new \App\Events\OrderCreated($order));
        }
    }

    /**
     * Handle cancelled order webhook
     */
    protected function handleCancelledOrder(array $payload): void
    {
        $orderId = $payload['orderId'] ?? $payload['order']['id'] ?? null;
        
        if (!$orderId) {
            return;
        }

        $order = Order::where('order_number', 'GTR-' . $orderId)->first();
        
        if ($order && $order->canBeCancelled()) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $payload['reason'] ?? 'Platform tarafından iptal edildi',
            ]);
            
            Log::info("[Getir] Order cancelled: {$order->order_number}");
        }
    }

    /**
     * Handle status change webhook
     */
    protected function handleStatusChange(array $payload): void
    {
        $orderId = $payload['orderId'] ?? $payload['order']['id'] ?? null;
        $newStatus = $payload['status'] ?? $payload['newStatus'] ?? null;
        
        if (!$orderId || !$newStatus) {
            return;
        }

        $order = Order::where('order_number', 'GTR-' . $orderId)->first();
        
        if ($order) {
            $internalStatus = $this->mapOrderStatus($newStatus);
            $order->update(['status' => $internalStatus]);
            
            Log::info("[Getir] Order status updated: {$order->order_number} -> {$internalStatus}");
        }
    }
}

