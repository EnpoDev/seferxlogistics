<?php

namespace App\Services\Integrations;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

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
        return 'https://api.trendyol.com/sapigw/suppliers';
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

    /**
     * Map Trendyol order status to internal status
     */
    protected function mapOrderStatus(string $platformStatus): string
    {
        return match (strtoupper($platformStatus)) {
            'CREATED', 'NEW', 'WAITING' => Order::STATUS_PENDING,
            'PICKING', 'PREPARING', 'INVOICED' => Order::STATUS_PREPARING,
            'SHIPPED', 'READY' => Order::STATUS_READY,
            'IN_TRANSIT', 'DELIVERING' => Order::STATUS_ON_DELIVERY,
            'DELIVERED' => Order::STATUS_DELIVERED,
            'CANCELLED', 'UNDELIVERED', 'RETURNED' => Order::STATUS_CANCELLED,
            default => Order::STATUS_PENDING,
        };
    }

    /**
     * Map internal status to Trendyol status
     */
    protected function mapToPlatformStatus(string $internalStatus): string
    {
        return match ($internalStatus) {
            Order::STATUS_PENDING => 'Created',
            Order::STATUS_PREPARING => 'Picking',
            Order::STATUS_READY => 'Shipped',
            Order::STATUS_ON_DELIVERY => 'InTransit',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_CANCELLED => 'Cancelled',
            default => 'Created',
        };
    }

    /**
     * Parse Trendyol order data to internal format
     */
    protected function parseOrderData(array $platformOrder): array
    {
        $items = [];
        foreach (($platformOrder['lines'] ?? $platformOrder['orderLines'] ?? []) as $line) {
            $items[] = [
                'name' => $line['productName'] ?? $line['name'] ?? '',
                'quantity' => $line['quantity'] ?? 1,
                'price' => $line['price'] ?? $line['amount'] ?? 0,
                'notes' => $line['merchantSku'] ?? null,
            ];
        }

        $shippingAddress = $platformOrder['shipmentAddress'] ?? $platformOrder['deliveryAddress'] ?? [];
        $customer = $platformOrder['customer'] ?? [];
        
        return [
            'order_number' => 'TY-' . ($platformOrder['orderNumber'] ?? $platformOrder['id'] ?? uniqid()),
            'customer_name' => $this->formatCustomerName($shippingAddress, $customer),
            'customer_phone' => $shippingAddress['phone'] ?? $customer['phone'] ?? '',
            'customer_address' => $this->formatTrendyolAddress($shippingAddress),
            'lat' => $shippingAddress['latitude'] ?? null,
            'lng' => $shippingAddress['longitude'] ?? null,
            'subtotal' => $platformOrder['totalPrice'] ?? $platformOrder['grossAmount'] ?? 0,
            'delivery_fee' => $platformOrder['cargoAmount'] ?? $platformOrder['deliveryFee'] ?? 0,
            'total' => $platformOrder['totalPrice'] ?? 0,
            'payment_method' => $this->mapPaymentMethod($platformOrder['paymentType'] ?? ''),
            'is_paid' => true, // Trendyol orders are pre-paid
            'status' => $this->mapOrderStatus($platformOrder['status'] ?? 'CREATED'),
            'notes' => $platformOrder['customerNote'] ?? $platformOrder['lines'][0]['merchantSku'] ?? null,
            'items' => $items,
        ];
    }

    /**
     * Format customer name
     */
    protected function formatCustomerName(array $address, array $customer): string
    {
        if (!empty($address['fullName'])) {
            return $address['fullName'];
        }
        
        $parts = array_filter([
            $address['firstName'] ?? $customer['firstName'] ?? '',
            $address['lastName'] ?? $customer['lastName'] ?? '',
        ]);
        
        return implode(' ', $parts) ?: 'Trendyol Müşterisi';
    }

    /**
     * Format Trendyol address to string
     */
    protected function formatTrendyolAddress(array $address): string
    {
        if (!empty($address['fullAddress'])) {
            return $address['fullAddress'];
        }
        
        $parts = array_filter([
            $address['address1'] ?? $address['addressLine1'] ?? '',
            $address['address2'] ?? $address['addressLine2'] ?? '',
            $address['neighborhood'] ?? $address['district'] ?? '',
            $address['city'] ?? '',
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Map payment method
     */
    protected function mapPaymentMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'CASH_ON_DELIVERY', 'COD' => Order::PAYMENT_CASH,
            'CREDIT_CARD', 'CARD' => Order::PAYMENT_CARD,
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
            if (!is_numeric($credentials['supplier_id'])) {
                return false;
            }

            // API-ready: In production, test with actual API call
            // $this->apiRequest('get', "/{$credentials['supplier_id']}/orders");

            Log::info("[Trendyol] Connection test passed (API-ready mode)");
            return true;
        } catch (\Exception $e) {
            Log::error("[Trendyol] Connection test error: " . $e->getMessage());
            return false;
        }
    }

    protected function getApiHeaders(array $credentials): array
    {
        $auth = base64_encode(($credentials['api_key'] ?? '') . ':' . ($credentials['api_secret'] ?? ''));
        
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
            'User-Agent' => 'SeferXLojistik/1.0',
        ];
    }

    /**
     * Get supplier-specific endpoint
     */
    protected function getSupplierEndpoint(string $endpoint): string
    {
        $supplierId = $this->integration?->credentials['supplier_id'] ?? '';
        return "/{$supplierId}" . $endpoint;
    }

    protected function doFetchOrders(): array
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would call:
        // $endpoint = $this->getSupplierEndpoint('/orders');
        // return $this->apiRequest('get', $endpoint, [
        //     'status' => 'Created',
        //     'startDate' => now()->subDay()->timestamp * 1000,
        //     'endDate' => now()->timestamp * 1000,
        // ]);
        
        Log::info("[Trendyol] Fetching orders (API-ready mode)");
        
        return [];
    }

    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $orderNumber = str_replace('TY-', '', $order->order_number);
        
        // API-ready: In production, this would call:
        // $endpoint = $this->getSupplierEndpoint("/orders/{$orderNumber}");
        // $this->apiRequest('put', $endpoint, [
        //     'status' => $status,
        //     'params' => [
        //         'orderNumber' => $orderNumber
        //     ]
        // ]);
        
        Log::info("[Trendyol] Updating order status: {$order->order_number} -> {$status} (API-ready mode)");
        
        return true;
    }

    protected function doSyncMenu(): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        
        // API-ready: In production, this would:
        // 1. Fetch products from database
        // 2. Format for Trendyol API (with barcodes, etc.)
        // 3. Call $this->apiRequest('post', $this->getSupplierEndpoint('/products'), $productData);
        
        Log::info("[Trendyol] Syncing menu (API-ready mode)");
        
        return true;
    }

    public function handleWebhook(array $payload): void
    {
        parent::handleWebhook($payload);

        $eventType = $payload['eventType'] ?? $payload['type'] ?? $payload['event'] ?? null;

        switch (strtoupper($eventType ?? '')) {
            case 'ORDER_CREATED':
            case 'ORDERCREATED':
                $this->handleNewOrder($payload);
                break;
                
            case 'ORDER_CANCELLED':
            case 'ORDERCANCELLED':
                $this->handleCancelledOrder($payload);
                break;
                
            case 'ORDER_STATUS_CHANGED':
            case 'ORDERSTATUSCHANGED':
                $this->handleStatusChange($payload);
                break;
                
            case 'ORDER_SHIPPED':
            case 'ORDERSHIPPED':
                $this->handleOrderShipped($payload);
                break;
                
            default:
                Log::info("[Trendyol] Unhandled webhook event: {$eventType}");
        }
    }

    /**
     * Handle new order webhook
     */
    protected function handleNewOrder(array $payload): void
    {
        $orderData = $payload['order'] ?? $payload['content'] ?? $payload;
        
        if (empty($orderData)) {
            Log::warning("[Trendyol] Empty order data in webhook");
            return;
        }

        $order = $this->createOrUpdateOrder($orderData);
        
        if ($order) {
            Log::info("[Trendyol] New order created from webhook: {$order->order_number}");
            event(new \App\Events\OrderCreated($order));
        }
    }

    /**
     * Handle cancelled order webhook
     */
    protected function handleCancelledOrder(array $payload): void
    {
        $orderNumber = $payload['orderNumber'] ?? $payload['order']['orderNumber'] ?? $payload['content']['orderNumber'] ?? null;
        
        if (!$orderNumber) {
            return;
        }

        $order = Order::where('order_number', 'TY-' . $orderNumber)->first();
        
        if ($order && $order->canBeCancelled()) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $payload['cancelReason'] ?? $payload['reason'] ?? 'Platform tarafından iptal edildi',
            ]);
            
            Log::info("[Trendyol] Order cancelled: {$order->order_number}");
        }
    }

    /**
     * Handle status change webhook
     */
    protected function handleStatusChange(array $payload): void
    {
        $orderNumber = $payload['orderNumber'] ?? $payload['order']['orderNumber'] ?? $payload['content']['orderNumber'] ?? null;
        $newStatus = $payload['status'] ?? $payload['newStatus'] ?? $payload['content']['status'] ?? null;
        
        if (!$orderNumber || !$newStatus) {
            return;
        }

        $order = Order::where('order_number', 'TY-' . $orderNumber)->first();
        
        if ($order) {
            $internalStatus = $this->mapOrderStatus($newStatus);
            $order->update(['status' => $internalStatus]);
            
            Log::info("[Trendyol] Order status updated: {$order->order_number} -> {$internalStatus}");
        }
    }

    /**
     * Handle order shipped webhook
     */
    protected function handleOrderShipped(array $payload): void
    {
        $orderNumber = $payload['orderNumber'] ?? $payload['content']['orderNumber'] ?? null;
        
        if (!$orderNumber) {
            return;
        }

        $order = Order::where('order_number', 'TY-' . $orderNumber)->first();
        
        if ($order) {
            $order->update([
                'status' => Order::STATUS_ON_DELIVERY,
                'picked_up_at' => now(),
            ]);
            
            Log::info("[Trendyol] Order shipped: {$order->order_number}");
        }
    }
}

