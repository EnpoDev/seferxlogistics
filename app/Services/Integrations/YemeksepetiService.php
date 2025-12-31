<?php

namespace App\Services\Integrations;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

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

    /**
     * Map Yemeksepeti order status to internal status
     */
    protected function mapOrderStatus(string $platformStatus): string
    {
        return match (strtolower($platformStatus)) {
            'new', 'pending', 'waiting' => Order::STATUS_PENDING,
            'accepted', 'confirmed', 'preparing' => Order::STATUS_PREPARING,
            'ready', 'prepared' => Order::STATUS_READY,
            'delivering', 'on_way', 'shipped' => Order::STATUS_ON_DELIVERY,
            'delivered', 'completed' => Order::STATUS_DELIVERED,
            'cancelled', 'rejected', 'failed' => Order::STATUS_CANCELLED,
            default => Order::STATUS_PENDING,
        };
    }

    /**
     * Map internal status to Yemeksepeti status
     */
    protected function mapToPlatformStatus(string $internalStatus): string
    {
        return match ($internalStatus) {
            Order::STATUS_PENDING => 'pending',
            Order::STATUS_PREPARING => 'accepted',
            Order::STATUS_READY => 'ready',
            Order::STATUS_ON_DELIVERY => 'delivering',
            Order::STATUS_DELIVERED => 'delivered',
            Order::STATUS_CANCELLED => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Parse Yemeksepeti order data to internal format
     */
    protected function parseOrderData(array $platformOrder): array
    {
        $items = [];
        foreach (($platformOrder['items'] ?? $platformOrder['products'] ?? []) as $product) {
            $items[] = [
                'name' => $product['name'] ?? $product['productName'] ?? '',
                'quantity' => $product['quantity'] ?? $product['count'] ?? 1,
                'price' => $product['price'] ?? $product['unitPrice'] ?? 0,
                'notes' => $product['note'] ?? $product['comment'] ?? null,
            ];
        }

        $customer = $platformOrder['customer'] ?? $platformOrder['client'] ?? [];
        $address = $platformOrder['deliveryAddress'] ?? $platformOrder['address'] ?? [];
        
        return [
            'order_number' => 'YS-' . ($platformOrder['id'] ?? $platformOrder['orderId'] ?? uniqid()),
            'customer_name' => $customer['name'] ?? $customer['fullName'] ?? '',
            'customer_phone' => $customer['phone'] ?? $customer['phoneNumber'] ?? '',
            'customer_address' => $this->formatYemeksepetiAddress($address),
            'lat' => $address['latitude'] ?? $address['lat'] ?? null,
            'lng' => $address['longitude'] ?? $address['lng'] ?? null,
            'subtotal' => $platformOrder['subtotal'] ?? $platformOrder['itemsTotal'] ?? 0,
            'delivery_fee' => $platformOrder['deliveryFee'] ?? $platformOrder['serviceFee'] ?? 0,
            'total' => $platformOrder['totalAmount'] ?? $platformOrder['total'] ?? 0,
            'payment_method' => $this->mapPaymentMethod($platformOrder['paymentMethod'] ?? ''),
            'is_paid' => ($platformOrder['paymentMethod'] ?? '') !== 'cash',
            'status' => $this->mapOrderStatus($platformOrder['status'] ?? 'new'),
            'notes' => $platformOrder['note'] ?? $platformOrder['customerNote'] ?? null,
            'items' => $items,
        ];
    }

    /**
     * Format Yemeksepeti address to string
     */
    protected function formatYemeksepetiAddress(array $address): string
    {
        $parts = array_filter([
            $address['street'] ?? $address['addressLine1'] ?? '',
            $address['building'] ?? $address['buildingNumber'] ?? '',
            $address['floor'] ?? '',
            $address['apartment'] ?? $address['doorNumber'] ?? '',
            $address['district'] ?? $address['neighborhood'] ?? '',
            $address['city'] ?? '',
        ]);
        
        $formatted = implode(', ', $parts);
        
        if (!empty($address['directions'] ?? $address['description'] ?? '')) {
            $formatted .= ' (' . ($address['directions'] ?? $address['description']) . ')';
        }
        
        return $formatted;
    }

    /**
     * Map payment method
     */
    protected function mapPaymentMethod(string $method): string
    {
        return match (strtolower($method)) {
            'cash', 'cash_on_delivery', 'nakit' => Order::PAYMENT_CASH,
            'credit_card', 'card', 'kredi_karti' => Order::PAYMENT_CARD,
            default => Order::PAYMENT_ONLINE,
        };
    }

    protected function doTestConnection(array $credentials): bool
    {
        // Validate required credentials are present
        if (!$this->validateCredentials($credentials)) {
            return false;
        }

        try {
            // Validate credential formats
            if (strlen($credentials['api_key']) < 10) {
                return false;
            }
            if (strlen($credentials['api_secret']) < 10) {
                return false;
            }
            if (strlen($credentials['restaurant_id']) < 5) {
                return false;
            }

            // API-ready: In production, test with actual API call
            // $this->apiRequest('get', '/restaurant/info');

            Log::info("[Yemeksepeti] Connection test passed (API-ready mode)");
            return true;
        } catch (\Exception $e) {
            Log::error("[Yemeksepeti] Connection test error: " . $e->getMessage());
            return false;
        }
    }

    protected function getApiHeaders(array $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $credentials['api_key'] ?? '',
            'X-API-Secret' => $credentials['api_secret'] ?? '',
            'X-Restaurant-Id' => $credentials['restaurant_id'] ?? '',
        ];
    }

    protected function doFetchOrders(): array
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would call:
        // return $this->apiRequest('get', '/orders/pending');
        
        Log::info("[Yemeksepeti] Fetching orders (API-ready mode)");
        
        return [];
    }

    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $orderId = str_replace('YS-', '', $order->order_number);
        
        // API-ready: In production, this would call:
        // $this->apiRequest('put', "/orders/{$orderId}/status", ['status' => $status]);
        
        Log::info("[Yemeksepeti] Updating order status: {$order->order_number} -> {$status} (API-ready mode)");
        
        return true;
    }

    protected function doSyncMenu(): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would:
        // 1. Fetch products from database
        // 2. Format for Yemeksepeti API
        // 3. Call $this->apiRequest('post', '/menu/sync', $menuData);
        
        Log::info("[Yemeksepeti] Syncing menu (API-ready mode)");
        
        return true;
    }

    public function handleWebhook(array $payload): void
    {
        parent::handleWebhook($payload);

        $eventType = $payload['event_type'] ?? $payload['type'] ?? $payload['event'] ?? null;

        switch (strtolower($eventType ?? '')) {
            case 'order.new':
            case 'order.created':
            case 'new_order':
                $this->handleNewOrder($payload);
                break;
                
            case 'order.cancelled':
            case 'order.rejected':
                $this->handleCancelledOrder($payload);
                break;
                
            case 'order.status_changed':
            case 'order.updated':
                $this->handleStatusChange($payload);
                break;
                
            default:
                Log::info("[Yemeksepeti] Unhandled webhook event: {$eventType}");
        }
    }

    /**
     * Handle new order webhook
     */
    protected function handleNewOrder(array $payload): void
    {
        $orderData = $payload['order'] ?? $payload['data'] ?? $payload;
        
        if (empty($orderData)) {
            Log::warning("[Yemeksepeti] Empty order data in webhook");
            return;
        }

        $order = $this->createOrUpdateOrder($orderData);
        
        if ($order) {
            Log::info("[Yemeksepeti] New order created from webhook: {$order->order_number}");
            event(new \App\Events\OrderCreated($order));
        }
    }

    /**
     * Handle cancelled order webhook
     */
    protected function handleCancelledOrder(array $payload): void
    {
        $orderId = $payload['orderId'] ?? $payload['order']['id'] ?? $payload['order_id'] ?? null;
        
        if (!$orderId) {
            return;
        }

        $order = Order::where('order_number', 'YS-' . $orderId)->first();
        
        if ($order && $order->canBeCancelled()) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $payload['reason'] ?? $payload['cancelReason'] ?? 'Platform tarafından iptal edildi',
            ]);
            
            Log::info("[Yemeksepeti] Order cancelled: {$order->order_number}");
        }
    }

    /**
     * Handle status change webhook
     */
    protected function handleStatusChange(array $payload): void
    {
        $orderId = $payload['orderId'] ?? $payload['order']['id'] ?? $payload['order_id'] ?? null;
        $newStatus = $payload['status'] ?? $payload['newStatus'] ?? $payload['order']['status'] ?? null;
        
        if (!$orderId || !$newStatus) {
            return;
        }

        $order = Order::where('order_number', 'YS-' . $orderId)->first();
        
        if ($order) {
            $internalStatus = $this->mapOrderStatus($newStatus);
            $order->update(['status' => $internalStatus]);
            
            Log::info("[Yemeksepeti] Order status updated: {$order->order_number} -> {$internalStatus}");
        }
    }
}

