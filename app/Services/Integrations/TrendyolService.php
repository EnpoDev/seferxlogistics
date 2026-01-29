<?php

namespace App\Services\Integrations;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolService extends BaseIntegrationService
{
    // İptal Nedenleri - Restaurant Kaynaklı (UnSupplied servisinde kullanılabilir)
    public const CANCEL_REASON_SUPPLY_PROBLEM = 621;        // Tedarik problemi
    public const CANCEL_REASON_STORE_CLOSED = 622;          // Mağaza kapalı
    public const CANCEL_REASON_CANNOT_PREPARE = 623;        // Mağaza siparişi hazırlayamıyor
    public const CANCEL_REASON_HIGH_DEMAND = 624;           // Yüksek yoğunluk / Kurye yok (Model 1 only)
    public const CANCEL_REASON_OUT_OF_AREA = 626;           // Alan Dışı (Model 1 only)
    public const CANCEL_REASON_ORDER_CONFUSION = 627;       // Sipariş karışıklığı

    // İptal Nedenleri - Sistem/Platform Kaynaklı (Bilgi amaçlı)
    public const CANCEL_REASON_SYSTEM_FRAUD = 69;
    public const CANCEL_REASON_WRONG_ADDRESS = 601;
    public const CANCEL_REASON_NOT_AT_ADDRESS = 602;
    public const CANCEL_REASON_FRAUD = 603;
    public const CANCEL_REASON_CUSTOMER_CANCELLED = 604;
    public const CANCEL_REASON_ORDER_LATE = 605;
    public const CANCEL_REASON_ADDRESS_MISMATCH = 607;
    public const CANCEL_REASON_NOT_ACCEPTED = 625;

    // Paket Statüleri
    public const STATUS_CREATED = 'Created';
    public const STATUS_PICKING = 'Picking';
    public const STATUS_INVOICED = 'Invoiced';
    public const STATUS_SHIPPED = 'Shipped';
    public const STATUS_DELIVERED = 'Delivered';
    public const STATUS_RETURNED = 'Returned';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_UNSUPPLIED = 'UnSupplied';

    public function getPlatform(): string
    {
        return 'trendyol';
    }

    public function getPlatformName(): string
    {
        return 'Trendyol Go';
    }

    public function getPlatformDescription(): string
    {
        return 'Trendyol Go by Uber Eats siparişlerini otomatik olarak alın ve yönetin.';
    }

    protected function getApiBaseUrl(): string
    {
        return config('services.trendyol.base_url', 'https://api.tgoapis.com');
    }

    /**
     * Get cancel reason label
     */
    public static function getCancelReasonLabel(int $reasonId): string
    {
        return match ($reasonId) {
            self::CANCEL_REASON_SUPPLY_PROBLEM => 'Tedarik problemi',
            self::CANCEL_REASON_STORE_CLOSED => 'Mağaza kapalı',
            self::CANCEL_REASON_CANNOT_PREPARE => 'Mağaza siparişi hazırlayamıyor',
            self::CANCEL_REASON_HIGH_DEMAND => 'Yüksek yoğunluk / Kurye yok',
            self::CANCEL_REASON_OUT_OF_AREA => 'Alan dışı',
            self::CANCEL_REASON_ORDER_CONFUSION => 'Sipariş karışıklığı',
            self::CANCEL_REASON_CUSTOMER_CANCELLED => 'Müşteri iptal talep etti',
            self::CANCEL_REASON_ORDER_LATE => 'Sipariş gecikti - Müşteri istemiyor',
            self::CANCEL_REASON_NOT_ACCEPTED => 'Kabul edilmedi',
            default => 'Bilinmeyen neden',
        };
    }

    /**
     * Get available cancel reasons for restaurant
     */
    public static function getRestaurantCancelReasons(): array
    {
        return [
            self::CANCEL_REASON_SUPPLY_PROBLEM => 'Tedarik problemi',
            self::CANCEL_REASON_STORE_CLOSED => 'Mağaza kapalı',
            self::CANCEL_REASON_CANNOT_PREPARE => 'Mağaza siparişi hazırlayamıyor',
            self::CANCEL_REASON_HIGH_DEMAND => 'Yüksek yoğunluk / Kurye yok (Model 1)',
            self::CANCEL_REASON_OUT_OF_AREA => 'Alan dışı (Model 1)',
            self::CANCEL_REASON_ORDER_CONFUSION => 'Sipariş karışıklığı',
        ];
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
                'label' => 'Satıcı ID (Supplier ID)',
                'type' => 'text',
                'required' => true,
            ],
            'store_id' => [
                'label' => 'Mağaza ID (Store ID)',
                'type' => 'text',
                'required' => false,
            ],
        ];
    }

    /**
     * Map Trendyol order status to internal status
     */
    protected function mapOrderStatus(string $platformStatus): string
    {
        return match (strtolower($platformStatus)) {
            'created' => Order::STATUS_PENDING,
            'picking' => Order::STATUS_PREPARING,
            'invoiced' => Order::STATUS_READY,
            'shipped' => Order::STATUS_ON_DELIVERY,
            'delivered' => Order::STATUS_DELIVERED,
            'cancelled', 'unsupplied', 'unpacked', 'returned' => Order::STATUS_CANCELLED,
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
            Order::STATUS_READY => 'Invoiced',
            Order::STATUS_ON_DELIVERY => 'Shipped',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_CANCELLED => 'Cancelled',
            default => 'Created',
        };
    }

    /**
     * Parse Trendyol order data to internal format (Yemek/Meal format)
     *
     * Actual Trendyol Yemek API structure:
     * - lines[].name: Product name
     * - lines[].price: Product price
     * - lines[].modifierProducts: Modifier options (at line level)
     * - lines[].extraIngredients: Extra ingredients (at line level)
     * - lines[].removedIngredients: Removed ingredients (at line level)
     * - lines[].items[].packageItemId: Item ID for cancellation
     * - address: Shipping address (not shipmentAddress)
     * - customerNote: Customer note (at root level)
     */
    protected function parseOrderData(array $platformOrder): array
    {
        $items = [];
        $packageItemIds = [];

        foreach (($platformOrder['lines'] ?? []) as $line) {
            // Product name is at line level
            $itemName = $line['name'] ?? '';
            $itemNotes = [];

            // Collect package item IDs from items
            $lineQuantity = 0;
            foreach (($line['items'] ?? []) as $item) {
                if (!empty($item['packageItemId'])) {
                    $packageItemIds[] = $item['packageItemId'];
                }
                if (!($item['isCancelled'] ?? false)) {
                    $lineQuantity++;
                }
            }

            // Use 1 as minimum quantity
            if ($lineQuantity < 1) {
                $lineQuantity = 1;
            }

            // Modifier products at line level (ekstra seçenekler - sos, içecek vb.)
            $modifiers = [];
            foreach (($line['modifierProducts'] ?? []) as $modifier) {
                $modifierName = $modifier['name'] ?? '';
                if ($modifierName) {
                    $modifiers[] = $modifierName;
                }
            }
            if (!empty($modifiers)) {
                $itemNotes[] = 'Seçenekler: ' . implode(', ', $modifiers);
            }

            // Extra ingredients at line level (ekstra malzemeler)
            $extras = [];
            foreach (($line['extraIngredients'] ?? []) as $extra) {
                $extraName = $extra['name'] ?? '';
                if ($extraName) {
                    $extras[] = '+' . $extraName;
                }
            }
            if (!empty($extras)) {
                $itemNotes[] = implode(', ', $extras);
            }

            // Removed ingredients at line level (çıkarılan malzemeler)
            $removed = [];
            foreach (($line['removedIngredients'] ?? []) as $removedItem) {
                $removedName = $removedItem['name'] ?? '';
                if ($removedName) {
                    $removed[] = '-' . $removedName;
                }
            }
            if (!empty($removed)) {
                $itemNotes[] = implode(', ', $removed);
            }

            $items[] = [
                'name' => $itemName,
                'quantity' => $lineQuantity,
                'price' => $line['price'] ?? $line['unitSellingPrice'] ?? 0,
                'notes' => !empty($itemNotes) ? implode(' | ', $itemNotes) : null,
            ];
        }

        // Address can be 'address' or 'shipmentAddress'
        $address = $platformOrder['address'] ?? $platformOrder['shipmentAddress'] ?? [];
        $customer = $platformOrder['customer'] ?? [];
        $payment = $platformOrder['payment'] ?? [];

        // Payment method parsing (mealCard, onDelivery)
        $paymentMethod = Order::PAYMENT_ONLINE;
        $isPaid = true;

        if (!empty($payment['onDelivery']) && is_array($payment['onDelivery'])) {
            // Kapıda ödeme
            $onDelivery = $payment['onDelivery'];
            $paymentMethod = ($onDelivery['paymentType'] ?? '') === 'CASH'
                ? Order::PAYMENT_CASH
                : Order::PAYMENT_ONLINE;
            $isPaid = false; // Kapıda ödenecek
        } elseif (!empty($payment['mealCard']) && is_array($payment['mealCard'])) {
            // Yemek kartı ile ödeme
            $paymentMethod = Order::PAYMENT_ONLINE;
            $isPaid = true;
        }

        // Calculate totals
        $total = $platformOrder['totalPrice'] ?? 0;
        $deliveryFee = 0;
        if (!empty($payment['mealCard']) && is_array($payment['mealCard'])) {
            $deliveryFee = $payment['mealCard']['deliveryFee'] ?? 0;
        } elseif (!empty($payment['onDelivery']) && is_array($payment['onDelivery'])) {
            $deliveryFee = $payment['onDelivery']['deliveryFee'] ?? 0;
        }

        // Customer note is at root level as 'customerNote'
        $customerNote = $platformOrder['customerNote'] ?? $customer['note'] ?? null;

        return [
            'order_number' => 'TGO-' . ($platformOrder['orderNumber'] ?? $platformOrder['id'] ?? uniqid()),
            'platform_order_id' => $platformOrder['orderId'] ?? $platformOrder['id'] ?? null,
            'platform_package_id' => $platformOrder['id'] ?? null,
            'platform_data' => [
                'packageItemIds' => array_filter($packageItemIds),
                'storeId' => $platformOrder['storeId'] ?? null,
                'orderCode' => $platformOrder['orderCode'] ?? null,
                'deliveryType' => $platformOrder['deliveryType'] ?? null,
                'preparationTime' => $platformOrder['preparationTime'] ?? null,
            ],
            'customer_name' => $this->formatCustomerName($address, $customer),
            'customer_phone' => $address['phone'] ?? '',
            'customer_address' => $this->formatTrendyolAddress($address),
            'lat' => isset($address['latitude']) ? (float) $address['latitude'] : null,
            'lng' => isset($address['longitude']) ? (float) $address['longitude'] : null,
            'subtotal' => $total - $deliveryFee,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'is_paid' => $isPaid,
            'status' => $this->mapOrderStatus($platformOrder['packageStatus'] ?? 'Created'),
            'notes' => $customerNote,
            'items' => $items,
            'source' => 'trendyol',
        ];
    }

    /**
     * Extract all package item IDs from a Trendyol order
     * Used for cancel operations
     */
    public function extractPackageItemIds(array $platformOrder): array
    {
        $ids = [];

        foreach (($platformOrder['lines'] ?? []) as $line) {
            foreach (($line['items'] ?? []) as $item) {
                if (!empty($item['packageItemId'])) {
                    $ids[] = $item['packageItemId'];
                }
            }
        }

        return $ids;
    }

    /**
     * Format customer name
     */
    protected function formatCustomerName(array $address, array $customer): string
    {
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
        $parts = array_filter([
            $address['address1'] ?? '',
            $address['address2'] ?? '',
            ($address['apartmentNumber'] ?? null) ? 'Apt: ' . $address['apartmentNumber'] : '',
            ($address['floor'] ?? null) ? 'Kat: ' . $address['floor'] : '',
            ($address['doorNumber'] ?? null) ? 'Kapı: ' . $address['doorNumber'] : '',
            $address['neighborhood'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
        ]);

        $fullAddress = implode(', ', $parts);

        if (!empty($address['addressDescription'])) {
            $fullAddress .= ' (' . $address['addressDescription'] . ')';
        }

        return $fullAddress;
    }

    /**
     * Get API headers for Trendyol Go
     */
    protected function getApiHeaders(array $credentials): array
    {
        $apiKey = $credentials['api_key'] ?? config('services.trendyol.api_key');
        $apiSecret = $credentials['api_secret'] ?? config('services.trendyol.api_secret');
        $auth = base64_encode($apiKey . ':' . $apiSecret);

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
            'x-agentname' => config('services.trendyol.agent_name', 'SeferXLojistik'),
            'x-executor-user' => config('services.trendyol.executor_email', 'info@seferx.com'),
            'User-Agent' => 'SeferXLojistik/1.0',
        ];
    }

    /**
     * Test connection with Trendyol Go API
     */
    protected function doTestConnection(array $credentials): bool
    {
        if (!$this->validateCredentials($credentials)) {
            return false;
        }

        try {
            $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
            $baseUrl = $this->getApiBaseUrl();
            // Yemek entegrasyonu için meal endpoint kullan
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->get($url, [
                    'size' => 1,
                    'page' => 0,
                ]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Connection test successful");
                return true;
            }

            Log::error("[Trendyol Go] Connection test failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Connection test error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch orders from Trendyol Go API
     *
     * @param array $statuses Package statuses to filter (default: ['Created'])
     */
    protected function doFetchOrders(array $statuses = ['Created']): array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        // Yemek entegrasyonu için meal endpoint kullan
        $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages";

        $params = [
            'packageStatuses' => implode(',', $statuses),
            'size' => 50,
            'page' => 0,
        ];

        if ($storeId) {
            $params['storeId'] = $storeId;
        }

        try {
            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(30)
                ->get($url, $params);

            if ($response->failed()) {
                Log::error("[Trendyol Go] Fetch orders failed: " . $response->status() . ' - ' . $response->body());
                return [];
            }

            $data = $response->json();
            $orders = $data['content'] ?? [];

            Log::info("[Trendyol Go] Fetched " . count($orders) . " orders");

            return $orders;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Fetch orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch orders by multiple statuses
     */
    public function fetchOrdersByStatuses(array $statuses): array
    {
        return $this->doFetchOrders($statuses);
    }

    /**
     * Fetch a single order by package ID
     */
    public function fetchOrderByPackageId(string $packageId): ?array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/{$packageId}";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("[Trendyol Go] Fetch order failed: " . $response->status() . ' - ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Fetch order error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update order status on Trendyol Go (Yemek/Meal integration)
     *
     * Status flow:
     * 1. Created -> Picking (Siparişi kabul et)
     * 2. Picking -> Invoiced (Sipariş hazırlandı)
     * 3. Invoiced -> Shipped (Yola çıktı - Model 1: kendi kurye)
     * 4. Shipped -> Delivered (Teslim edildi - Model 1: kendi kurye)
     */
    protected function doUpdateOrderStatus(Order $order, string $status): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);

        return match (strtolower($status)) {
            'picking' => $this->acceptOrderByPackageId($packageId),
            'invoiced' => $this->markPreparedByPackageId($packageId),
            'shipped' => $this->markShippedByPackageId($packageId),
            'delivered' => $this->markDeliveredByPackageId($packageId),
            default => false,
        };
    }

    /**
     * Accept order (Picking status) - Siparişi Kabul Et
     *
     * @param Order $order
     * @param int $preparationTime Hazırlık süresi (dakika)
     */
    public function acceptOrder(Order $order, int $preparationTime = 15): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);
        return $this->acceptOrderByPackageId($packageId, $preparationTime);
    }

    /**
     * Accept order by package ID
     */
    public function acceptOrderByPackageId(string $packageId, int $preparationTime = 15): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // Yemek entegrasyonu: PUT /packages/picked with body
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/picked";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, [
                    'packageId' => $packageId,
                    'preparationTime' => $preparationTime,
                ]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Order accepted: {$packageId}, prep time: {$preparationTime} min");
                return true;
            }

            Log::error("[Trendyol Go] Accept order failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Accept order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark order as prepared (Invoiced status) - Sipariş Hazırlandı
     */
    public function markOrderPrepared(Order $order): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);
        return $this->markPreparedByPackageId($packageId);
    }

    /**
     * Mark order as prepared by package ID
     */
    public function markPreparedByPackageId(string $packageId): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // Yemek entegrasyonu: PUT /packages/invoiced with body
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/invoiced";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, [
                    'packageId' => $packageId,
                    'actualDate' => now()->timestamp * 1000, // Timestamp in milliseconds
                ]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Order marked as prepared: {$packageId}");
                return true;
            }

            Log::error("[Trendyol Go] Mark prepared failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Mark prepared error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark order as shipped (for own courier delivery - Model 1)
     *
     * @param Order $order
     * @param int|null $actualDate Optional timestamp in milliseconds
     */
    public function markOrderShipped(Order $order, ?int $actualDate = null): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);
        return $this->markShippedByPackageId($packageId, $actualDate);
    }

    /**
     * Mark order as shipped by package ID
     *
     * @param string $packageId
     * @param int|null $actualDate Optional timestamp in milliseconds (if null, current time is used by API)
     */
    public function markShippedByPackageId(string $packageId, ?int $actualDate = null): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // Model 1 için manual-shipped endpoint
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/{$packageId}/manual-shipped";

            // Body is optional, actualDate can be sent if provided
            $body = [];
            if ($actualDate !== null) {
                $body['actualDate'] = $actualDate;
            }

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, $body);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Order marked as shipped: {$packageId}");
                return true;
            }

            Log::error("[Trendyol Go] Mark shipped failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Mark shipped error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark order as delivered (for own courier delivery - Model 1)
     *
     * @param Order $order
     * @param int|null $actualDate Optional timestamp in milliseconds
     */
    public function markOrderDelivered(Order $order, ?int $actualDate = null): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);
        return $this->markDeliveredByPackageId($packageId, $actualDate);
    }

    /**
     * Mark order as delivered by package ID
     *
     * @param string $packageId
     * @param int|null $actualDate Optional timestamp in milliseconds (if null, current time is used by API)
     */
    public function markDeliveredByPackageId(string $packageId, ?int $actualDate = null): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // Model 1 için manual-delivered endpoint
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/{$packageId}/manual-delivered";

            // Body is optional, actualDate can be sent if provided
            $body = [];
            if ($actualDate !== null) {
                $body['actualDate'] = $actualDate;
            }

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, $body);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Order marked as delivered: {$packageId}");
                return true;
            }

            Log::error("[Trendyol Go] Mark delivered failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Mark delivered error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel order (UnSupplied) - Sipariş İptali
     *
     * Full iptal: Tüm packageItemId'leri itemIdList'e ekleyin
     * Kısmi iptal: Sadece iptal edilecek ürünlerin packageItemId'lerini ekleyin
     *
     * @param Order $order
     * @param array $itemIds Array of packageItemId values from order lines
     * @param int $reasonId Cancel reason (use CANCEL_REASON_* constants)
     */
    public function cancelOrder(Order $order, array $itemIds, int $reasonId = self::CANCEL_REASON_SUPPLY_PROBLEM): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);
        return $this->cancelOrderByPackageId($packageId, $itemIds, $reasonId);
    }

    /**
     * Cancel order by package ID
     *
     * @param string $packageId Package ID
     * @param array $itemIds Array of packageItemId values (for full cancel, include all items)
     * @param int $reasonId Cancel reason (621-627 for restaurant-initiated)
     */
    public function cancelOrderByPackageId(string $packageId, array $itemIds, int $reasonId = self::CANCEL_REASON_SUPPLY_PROBLEM): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            $url = "{$baseUrl}/integrator/order/meal/suppliers/{$supplierId}/packages/unsupplied";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, [
                    'packageId' => $packageId,
                    'itemIdList' => $itemIds, // API expects 'itemIdList' not 'packageItemIdList'
                    'reasonId' => $reasonId,
                ]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Order cancelled: {$packageId}, reason: {$reasonId}");
                return true;
            }

            Log::error("[Trendyol Go] Cancel order failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Cancel order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel order with automatic item ID extraction
     * This method fetches the order from Trendyol to get all item IDs
     */
    public function cancelFullOrder(Order $order, int $reasonId = self::CANCEL_REASON_SUPPLY_PROBLEM): bool
    {
        $packageId = $order->platform_package_id ?? str_replace('TGO-', '', $order->order_number);

        // First try to get item IDs from stored platform_data
        $platformData = $order->platform_data ?? [];
        $packageItemIds = $platformData['packageItemIds'] ?? [];

        // If not stored, fetch from API
        if (empty($packageItemIds)) {
            $orderData = $this->fetchOrderByPackageId($packageId);
            if ($orderData) {
                $packageItemIds = $this->extractPackageItemIds($orderData);
            }
        }

        if (empty($packageItemIds)) {
            Log::error("[Trendyol Go] Cannot cancel order: no package item IDs found for {$packageId}");
            return false;
        }

        return $this->cancelOrderByPackageId($packageId, $packageItemIds, $reasonId);
    }

    /**
     * Legacy method - Cancel order items (deprecated, use cancelOrder instead)
     * @deprecated Use cancelOrder() instead
     */
    public function cancelOrderItems(Order $order, array $itemIds, int $reasonId = self::CANCEL_REASON_SUPPLY_PROBLEM, ?string $description = null): bool
    {
        return $this->cancelOrder($order, $itemIds, $reasonId);
    }

    // =========================================================================
    // FATURA ENTEGRASYONU (Invoice Integration)
    // =========================================================================

    /**
     * Send invoice link for an order
     *
     * YASAL ZORUNLULUK: Gönderilen fatura bağlantılarının 10 yıl boyunca erişilebilir olması gerekir.
     *
     * @param Order $order The order to send invoice for
     * @param string $invoiceLink URL to the invoice (must be accessible for 10 years)
     * @return bool
     */
    public function sendInvoiceLink(Order $order, string $invoiceLink): bool
    {
        $orderId = $order->platform_order_id ?? $order->platform_data['orderId'] ?? null;

        if (!$orderId) {
            Log::error("[Trendyol Go] Cannot send invoice: orderId not found for order {$order->order_number}");
            return false;
        }

        return $this->sendInvoiceLinkByOrderId($orderId, $invoiceLink);
    }

    /**
     * Send invoice link by order ID
     *
     * @param string $orderId The Trendyol order ID (not package ID)
     * @param string $invoiceLink URL to the invoice
     * @param int $channelId Channel ID (always 4 for Yemek integration)
     * @return bool
     */
    public function sendInvoiceLinkByOrderId(string $orderId, string $invoiceLink, int $channelId = 4): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // POST /integrator/invoice/meal/suppliers/{supplierId}/supplier-invoice-links/{channelId}/{serviceSourceId}
            $url = "{$baseUrl}/integrator/invoice/meal/suppliers/{$supplierId}/supplier-invoice-links/{$channelId}/{$orderId}";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->post($url, [
                    'invoiceLink' => $invoiceLink,
                ]);

            if ($response->status() === 201 || $response->successful()) {
                Log::info("[Trendyol Go] Invoice link sent for order: {$orderId}");
                return true;
            }

            Log::error("[Trendyol Go] Send invoice link failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Send invoice link error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete invoice link for an order
     *
     * @param Order $order The order to delete invoice for
     * @return bool
     */
    public function deleteInvoiceLink(Order $order): bool
    {
        $orderId = $order->platform_order_id ?? $order->platform_data['orderId'] ?? null;

        if (!$orderId) {
            Log::error("[Trendyol Go] Cannot delete invoice: orderId not found for order {$order->order_number}");
            return false;
        }

        return $this->deleteInvoiceLinkByOrderId($orderId);
    }

    /**
     * Delete invoice link by order ID
     *
     * @param string $orderId The Trendyol order ID
     * @param int $channelId Channel ID (always 4 for Yemek integration)
     * @return bool
     */
    public function deleteInvoiceLinkByOrderId(string $orderId, int $channelId = 4): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            // DELETE endpoint for invoice
            $url = "{$baseUrl}/integrator/invoice/meal/suppliers/{$supplierId}/supplier-invoice-links/{$channelId}/{$orderId}";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->delete($url);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Invoice link deleted for order: {$orderId}");
                return true;
            }

            Log::error("[Trendyol Go] Delete invoice link failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Delete invoice link error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync menu/products with Trendyol Go
     */
    protected function doSyncMenu(): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        // Trendyol Go menü senkronizasyonu için ürünleri hazırla
        // Bu özellik daha karmaşık - kategori ve marka bilgileri gerekiyor

        Log::info("[Trendyol Go] Menu sync - feature requires additional implementation");

        // TODO: Ürünleri veritabanından çek ve Trendyol formatına dönüştür
        // POST https://api.tgoapis.com/integrator/product/grocery/suppliers/{supplierId}/products

        return true;
    }

    /**
     * Handle incoming webhook from Trendyol Go
     */
    public function handleWebhook(array $payload): void
    {
        parent::handleWebhook($payload);

        $eventType = $payload['eventType'] ?? $payload['type'] ?? $payload['event'] ?? null;

        Log::info("[Trendyol Go] Webhook received: " . ($eventType ?? 'unknown'), ['payload' => $payload]);

        switch (strtoupper($eventType ?? '')) {
            case 'ORDER_CREATED':
            case 'ORDERCREATED':
            case 'NEW_ORDER':
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

            default:
                Log::info("[Trendyol Go] Unhandled webhook event: {$eventType}");
        }
    }

    /**
     * Handle new order webhook
     */
    protected function handleNewOrder(array $payload): void
    {
        $orderData = $payload['order'] ?? $payload['content'] ?? $payload;

        if (empty($orderData)) {
            Log::warning("[Trendyol Go] Empty order data in webhook");
            return;
        }

        $order = $this->createOrUpdateOrder($orderData);

        if ($order) {
            Log::info("[Trendyol Go] New order created from webhook: {$order->order_number}");
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

        $order = Order::where('order_number', 'TGO-' . $orderNumber)
            ->orWhere('order_number', 'TY-' . $orderNumber)
            ->first();

        if ($order && $order->canBeCancelled()) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $payload['cancelReason'] ?? $payload['reason'] ?? 'Platform tarafından iptal edildi',
            ]);

            Log::info("[Trendyol Go] Order cancelled: {$order->order_number}");
        }
    }

    /**
     * Handle status change webhook
     */
    protected function handleStatusChange(array $payload): void
    {
        $orderNumber = $payload['orderNumber'] ?? $payload['order']['orderNumber'] ?? $payload['content']['orderNumber'] ?? null;
        $newStatus = $payload['status'] ?? $payload['packageStatus'] ?? $payload['newStatus'] ?? null;

        if (!$orderNumber || !$newStatus) {
            return;
        }

        $order = Order::where('order_number', 'TGO-' . $orderNumber)
            ->orWhere('order_number', 'TY-' . $orderNumber)
            ->first();

        if ($order) {
            $internalStatus = $this->mapOrderStatus($newStatus);
            $order->update(['status' => $internalStatus]);

            Log::info("[Trendyol Go] Order status updated: {$order->order_number} -> {$internalStatus}");
        }
    }

    /**
     * Get invoice amount limits for an order
     */
    public function getInvoiceAmountLimits(string $orderId): ?array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            $url = "{$baseUrl}/integrator/order/grocery/suppliers/{$supplierId}/orders/{$orderId}/invoice-amount";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Get invoice limits error: " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // YEMEK ENTEGRASYONU - MENÜ YÖNETİMİ (Restaurant/Meal Integration)
    // =========================================================================

    /**
     * Get restaurant menu products
     *
     * Returns all menu items including ingredients, modifier groups, products and sections
     */
    public function getMenuProducts(): ?array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for menu operations");
            return null;
        }

        try {
            $url = "{$baseUrl}/integrator/product/meal/suppliers/{$supplierId}/stores/{$storeId}/products";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("[Trendyol Go] Menu fetched successfully", [
                    'products' => count($data['products'] ?? []),
                    'sections' => count($data['sections'] ?? []),
                    'modifierGroups' => count($data['modifierGroups'] ?? []),
                ]);
                return $data;
            }

            Log::error("[Trendyol Go] Fetch menu failed: " . $response->status() . ' - ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Fetch menu error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update section/category status (ACTIVE/PASSIVE)
     *
     * @param string $sectionName The section name (URL encoded if needed)
     * @param string $status 'ACTIVE' or 'PASSIVE'
     */
    public function updateSectionStatus(string $sectionName, string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for section status update");
            return false;
        }

        $status = strtoupper($status);
        if (!in_array($status, ['ACTIVE', 'PASSIVE'])) {
            Log::error("[Trendyol Go] Invalid status. Must be ACTIVE or PASSIVE");
            return false;
        }

        try {
            $encodedSectionName = rawurlencode($sectionName);
            $url = "{$baseUrl}/integrator/product/meal/suppliers/{$supplierId}/stores/{$storeId}/sections/{$encodedSectionName}/status";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, ['status' => $status]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Section status updated: {$sectionName} -> {$status}");
                return true;
            }

            Log::error("[Trendyol Go] Section status update failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Section status update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable a menu section/category
     */
    public function enableSection(string $sectionName): bool
    {
        return $this->updateSectionStatus($sectionName, 'ACTIVE');
    }

    /**
     * Disable a menu section/category
     */
    public function disableSection(string $sectionName): bool
    {
        return $this->updateSectionStatus($sectionName, 'PASSIVE');
    }

    /**
     * Update product status (ACTIVE/PASSIVE)
     *
     * @param int|string $productId The product ID from Trendyol
     * @param string $status 'ACTIVE' or 'PASSIVE'
     */
    public function updateProductStatus(int|string $productId, string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for product status update");
            return false;
        }

        $status = strtoupper($status);
        if (!in_array($status, ['ACTIVE', 'PASSIVE'])) {
            Log::error("[Trendyol Go] Invalid status. Must be ACTIVE or PASSIVE");
            return false;
        }

        try {
            $url = "{$baseUrl}/integrator/product/meal/suppliers/{$supplierId}/stores/{$storeId}/products/{$productId}/status";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, ['status' => $status]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Product status updated: {$productId} -> {$status}");
                return true;
            }

            Log::error("[Trendyol Go] Product status update failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Product status update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable a product (set to ACTIVE)
     */
    public function enableProduct(int|string $productId): bool
    {
        return $this->updateProductStatus($productId, 'ACTIVE');
    }

    /**
     * Disable a product (set to PASSIVE)
     */
    public function disableProduct(int|string $productId): bool
    {
        return $this->updateProductStatus($productId, 'PASSIVE');
    }

    /**
     * Bulk update multiple products status
     *
     * @param array $productIds Array of product IDs
     * @param string $status 'ACTIVE' or 'PASSIVE'
     * @return array Results with product ID as key and success status as value
     */
    public function bulkUpdateProductStatus(array $productIds, string $status): array
    {
        $results = [];

        foreach ($productIds as $productId) {
            $results[$productId] = $this->updateProductStatus($productId, $status);
        }

        $successCount = count(array_filter($results));
        Log::info("[Trendyol Go] Bulk product status update: {$successCount}/" . count($productIds) . " successful");

        return $results;
    }

    /**
     * Bulk update multiple sections status
     *
     * @param array $sectionNames Array of section names
     * @param string $status 'ACTIVE' or 'PASSIVE'
     * @return array Results with section name as key and success status as value
     */
    public function bulkUpdateSectionStatus(array $sectionNames, string $status): array
    {
        $results = [];

        foreach ($sectionNames as $sectionName) {
            $results[$sectionName] = $this->updateSectionStatus($sectionName, $status);
        }

        $successCount = count(array_filter($results));
        Log::info("[Trendyol Go] Bulk section status update: {$successCount}/" . count($sectionNames) . " successful");

        return $results;
    }

    // =========================================================================
    // RESTORAN ENTEGRASYONU (Restaurant Integration)
    // =========================================================================

    /**
     * Get all restaurants for the supplier
     *
     * @param int $page Page number (default 0)
     * @param int $size Items per page (max 50)
     */
    public function getRestaurants(int $page = 0, int $size = 50): ?array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $baseUrl = $this->getApiBaseUrl();

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(30)
                ->get($url, [
                    'page' => $page,
                    'size' => min($size, 50),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("[Trendyol Go] Fetched restaurants", [
                    'count' => count($data['restaurants'] ?? []),
                    'totalPages' => $data['totalPages'] ?? 1,
                ]);
                return $data;
            }

            Log::error("[Trendyol Go] Fetch restaurants failed: " . $response->status() . ' - ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Fetch restaurants error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get restaurant delivery areas
     */
    public function getDeliveryAreas(): ?array
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for delivery areas");
            return null;
        }

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores/{$storeId}/delivery-areas";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("[Trendyol Go] Fetched delivery areas", [
                    'areaCount' => count($data['areas'] ?? []),
                    'radius' => $data['radius'] ?? null,
                ]);
                return $data;
            }

            Log::error("[Trendyol Go] Fetch delivery areas failed: " . $response->status() . ' - ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Fetch delivery areas error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update restaurant delivery areas
     *
     * @param array $areas Array of delivery area objects with:
     *   - coordinates: MULTIPOLYGON WKT string
     *   - minBasketPrice: Minimum order amount
     *   - averageDeliveryTime: ['min' => int, 'max' => int] (must be multiples of 5)
     *   - status: 'AVAILABLE' or 'UNAVAILABLE'
     */
    public function updateDeliveryAreas(array $areas): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for delivery areas update");
            return false;
        }

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores/{$storeId}/delivery-areas";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(30)
                ->put($url, ['areas' => $areas]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Delivery areas updated successfully");
                return true;
            }

            Log::error("[Trendyol Go] Update delivery areas failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Update delivery areas error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update restaurant working hours
     *
     * @param array $workingHours Array of working hour objects with:
     *   - dayOfWeek: MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY, SUNDAY
     *   - openingTime: HH:mm:ss format (e.g., "08:00:00")
     *   - closingTime: HH:mm:ss format (e.g., "22:00:00")
     */
    public function updateWorkingHours(array $workingHours): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for working hours update");
            return false;
        }

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores/{$storeId}/working-hours";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, ['workingHours' => $workingHours]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Working hours updated successfully");
                return true;
            }

            Log::error("[Trendyol Go] Update working hours failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Update working hours error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update restaurant working status (OPEN/CLOSED)
     *
     * @param string $status 'OPEN' or 'CLOSED'
     */
    public function updateWorkingStatus(string $status): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for working status update");
            return false;
        }

        $status = strtoupper($status);
        if (!in_array($status, ['OPEN', 'CLOSED'])) {
            Log::error("[Trendyol Go] Invalid status. Must be OPEN or CLOSED");
            return false;
        }

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores/{$storeId}/status";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, ['status' => $status]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Working status updated: {$status}");
                return true;
            }

            Log::error("[Trendyol Go] Update working status failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Update working status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Open the restaurant
     */
    public function openRestaurant(): bool
    {
        return $this->updateWorkingStatus('OPEN');
    }

    /**
     * Close the restaurant
     */
    public function closeRestaurant(): bool
    {
        return $this->updateWorkingStatus('CLOSED');
    }

    /**
     * Update restaurant average delivery time
     *
     * @param int $min Minimum delivery time in minutes (15-85, must be multiple of 5)
     * @param int $max Maximum delivery time in minutes (20-90, must be multiple of 5)
     */
    public function updateDeliveryTime(int $min, int $max): bool
    {
        $credentials = $this->integration?->credentials ?? [];
        $supplierId = $credentials['supplier_id'] ?? config('services.trendyol.supplier_id');
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');
        $baseUrl = $this->getApiBaseUrl();

        if (!$storeId) {
            Log::error("[Trendyol Go] Store ID required for delivery time update");
            return false;
        }

        // Validate min/max values
        if ($min % 5 !== 0 || $max % 5 !== 0) {
            Log::error("[Trendyol Go] Delivery time values must be multiples of 5");
            return false;
        }

        if ($min < 15 || $min > 85) {
            Log::error("[Trendyol Go] Min delivery time must be between 15-85");
            return false;
        }

        if ($max < 20 || $max > 90) {
            Log::error("[Trendyol Go] Max delivery time must be between 20-90");
            return false;
        }

        if ($min >= $max) {
            Log::error("[Trendyol Go] Min must be less than max");
            return false;
        }

        try {
            $url = "{$baseUrl}/integrator/store/meal/suppliers/{$supplierId}/stores/{$storeId}/average-delivery-time";

            $response = Http::withHeaders($this->getApiHeaders($credentials))
                ->timeout(15)
                ->put($url, [
                    'min' => $min,
                    'max' => $max,
                ]);

            if ($response->successful()) {
                Log::info("[Trendyol Go] Delivery time updated: {$min}-{$max} min");
                return true;
            }

            Log::error("[Trendyol Go] Update delivery time failed: " . $response->status() . ' - ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[Trendyol Go] Update delivery time error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get complete restaurant info including working hours and status
     */
    public function getRestaurantInfo(): ?array
    {
        $restaurants = $this->getRestaurants();

        if (!$restaurants || empty($restaurants['restaurants'])) {
            return null;
        }

        $credentials = $this->integration?->credentials ?? [];
        $storeId = $credentials['store_id'] ?? config('services.trendyol.store_id');

        // Find the specific store or return first one
        foreach ($restaurants['restaurants'] as $restaurant) {
            if ($storeId && $restaurant['id'] == $storeId) {
                return $restaurant;
            }
        }

        return $restaurants['restaurants'][0] ?? null;
    }

    /**
     * Get full restaurant dashboard data
     * Combines restaurant info, delivery areas, and menu
     */
    public function getRestaurantDashboard(): array
    {
        return [
            'restaurant' => $this->getRestaurantInfo(),
            'deliveryAreas' => $this->getDeliveryAreas(),
            'menu' => $this->getMenuProducts(),
        ];
    }
}
