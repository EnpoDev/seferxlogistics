<?php

namespace Tests\Unit\Services;

use App\Services\Integrations\TrendyolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendyolServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TrendyolService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrendyolService();
    }

    /** @test */
    public function it_returns_correct_platform_name(): void
    {
        $this->assertEquals('trendyol', $this->service->getPlatform());
    }

    /** @test */
    public function it_has_required_credentials(): void
    {
        $credentials = $this->service->getRequiredCredentials();

        $this->assertArrayHasKey('api_key', $credentials);
        $this->assertArrayHasKey('api_secret', $credentials);
        $this->assertArrayHasKey('supplier_id', $credentials);
    }

    /** @test */
    public function it_has_cancel_reason_constants(): void
    {
        $this->assertEquals(621, TrendyolService::CANCEL_REASON_SUPPLY_PROBLEM);
        $this->assertEquals(622, TrendyolService::CANCEL_REASON_STORE_CLOSED);
        $this->assertEquals(623, TrendyolService::CANCEL_REASON_CANNOT_PREPARE);
        $this->assertEquals(624, TrendyolService::CANCEL_REASON_HIGH_DEMAND);
        $this->assertEquals(626, TrendyolService::CANCEL_REASON_OUT_OF_AREA);
        $this->assertEquals(627, TrendyolService::CANCEL_REASON_ORDER_CONFUSION);
    }

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertEquals('Created', TrendyolService::STATUS_CREATED);
        $this->assertEquals('Picking', TrendyolService::STATUS_PICKING);
        $this->assertEquals('Invoiced', TrendyolService::STATUS_INVOICED);
        $this->assertEquals('Shipped', TrendyolService::STATUS_SHIPPED);
        $this->assertEquals('Delivered', TrendyolService::STATUS_DELIVERED);
        $this->assertEquals('Cancelled', TrendyolService::STATUS_CANCELLED);
        $this->assertEquals('UnSupplied', TrendyolService::STATUS_UNSUPPLIED);
    }

    /** @test */
    public function it_returns_cancel_reason_labels(): void
    {
        $this->assertEquals('Tedarik problemi', TrendyolService::getCancelReasonLabel(621));
        $this->assertEquals('Mağaza kapalı', TrendyolService::getCancelReasonLabel(622));
        $this->assertEquals('Mağaza siparişi hazırlayamıyor', TrendyolService::getCancelReasonLabel(623));
        $this->assertEquals('Yüksek yoğunluk / Kurye yok', TrendyolService::getCancelReasonLabel(624));
        $this->assertEquals('Alan dışı', TrendyolService::getCancelReasonLabel(626));
        $this->assertEquals('Sipariş karışıklığı', TrendyolService::getCancelReasonLabel(627));
    }

    /** @test */
    public function it_returns_restaurant_cancel_reasons(): void
    {
        $reasons = TrendyolService::getRestaurantCancelReasons();

        $this->assertIsArray($reasons);
        $this->assertArrayHasKey(621, $reasons);
        $this->assertArrayHasKey(622, $reasons);
        $this->assertArrayHasKey(623, $reasons);
        $this->assertArrayHasKey(624, $reasons);
        $this->assertArrayHasKey(626, $reasons);
        $this->assertArrayHasKey(627, $reasons);
    }

    /** @test */
    public function it_extracts_package_item_ids_from_order(): void
    {
        $mockOrder = [
            'lines' => [
                [
                    'name' => 'Test Product',
                    'items' => [
                        ['packageItemId' => '1001'],
                        ['packageItemId' => '1002'],
                    ]
                ],
                [
                    'name' => 'Another Product',
                    'items' => [
                        ['packageItemId' => '1003'],
                    ]
                ]
            ]
        ];

        $itemIds = $this->service->extractPackageItemIds($mockOrder);

        $this->assertCount(3, $itemIds);
        $this->assertContains('1001', $itemIds);
        $this->assertContains('1002', $itemIds);
        $this->assertContains('1003', $itemIds);
    }

    /** @test */
    public function it_returns_empty_array_when_no_items(): void
    {
        $mockOrder = [
            'lines' => []
        ];

        $itemIds = $this->service->extractPackageItemIds($mockOrder);

        $this->assertIsArray($itemIds);
        $this->assertEmpty($itemIds);
    }

    /** @test */
    public function it_handles_missing_lines_in_order(): void
    {
        $mockOrder = [];

        $itemIds = $this->service->extractPackageItemIds($mockOrder);

        $this->assertIsArray($itemIds);
        $this->assertEmpty($itemIds);
    }

    /** @test */
    public function it_parses_order_data_correctly(): void
    {
        $mockPlatformOrder = [
            'id' => 'pkg-123',
            'orderNumber' => '10851087394',
            'orderId' => '1010851087394',
            'packageStatus' => 'Created',
            'totalPrice' => 2500,
            'lines' => [
                [
                    'name' => 'Tavuk Şiş',
                    'price' => 2500,
                    'items' => [
                        ['packageItemId' => '1001382379771', 'isCancelled' => false]
                    ],
                    'modifierProducts' => [],
                    'extraIngredients' => [],
                    'removedIngredients' => [],
                ]
            ],
            'address' => [
                'firstName' => 'Test',
                'lastName' => 'Customer',
                'phone' => '05551234567',
                'address1' => 'Test Address',
                'neighborhood' => 'Merkez Mah',
                'district' => 'Seferihisar',
                'city' => 'İzmir',
                'latitude' => 38.194866,
                'longitude' => 26.831853,
            ],
            'customer' => [
                'firstName' => 'Test',
                'lastName' => 'Customer',
            ],
            'payment' => [
                'paymentType' => 'MEAL_CARD',
                'mealCard' => ['deliveryFee' => 0],
            ],
            'customerNote' => 'Test note',
        ];

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseOrderData');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->service, $mockPlatformOrder);

        $this->assertEquals('TGO-10851087394', $parsed['order_number']);
        $this->assertEquals('pkg-123', $parsed['platform_package_id']);
        $this->assertEquals('Test Customer', $parsed['customer_name']);
        $this->assertEquals('05551234567', $parsed['customer_phone']);
        $this->assertEquals(2500, $parsed['total']);
        $this->assertEquals('pending', $parsed['status']);
        $this->assertEquals('trendyol', $parsed['source']);
        $this->assertCount(1, $parsed['items']);
        $this->assertEquals('Tavuk Şiş', $parsed['items'][0]['name']);
    }

    /** @test */
    public function it_parses_cash_on_delivery_payment(): void
    {
        $mockPlatformOrder = [
            'id' => 'pkg-123',
            'orderNumber' => '10851087394',
            'packageStatus' => 'Created',
            'totalPrice' => 2500,
            'lines' => [],
            'address' => ['firstName' => 'Test', 'lastName' => 'Customer'],
            'customer' => [],
            'payment' => [
                'paymentType' => 'ON_DELIVERY',
                'onDelivery' => [
                    'paymentType' => 'CASH',
                    'deliveryFee' => 50,
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseOrderData');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->service, $mockPlatformOrder);

        $this->assertEquals('cash', $parsed['payment_method']);
        $this->assertFalse($parsed['is_paid']);
        $this->assertEquals(50, $parsed['delivery_fee']);
    }

    /** @test */
    public function it_parses_modifiers_and_ingredients(): void
    {
        $mockPlatformOrder = [
            'id' => 'pkg-123',
            'orderNumber' => '10851087394',
            'packageStatus' => 'Created',
            'totalPrice' => 3500,
            'lines' => [
                [
                    'name' => 'Döner',
                    'price' => 3000,
                    'items' => [
                        ['packageItemId' => '1001', 'isCancelled' => false]
                    ],
                    'modifierProducts' => [
                        ['name' => 'Acılı Sos'],
                        ['name' => 'Ayran'],
                    ],
                    'extraIngredients' => [
                        ['name' => 'Ekstra Et'],
                    ],
                    'removedIngredients' => [
                        ['name' => 'Soğan'],
                    ],
                ]
            ],
            'address' => ['firstName' => 'Test', 'lastName' => 'Customer'],
            'customer' => [],
            'payment' => [],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseOrderData');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->service, $mockPlatformOrder);

        $itemNotes = $parsed['items'][0]['notes'];

        $this->assertStringContainsString('Seçenekler:', $itemNotes);
        $this->assertStringContainsString('Acılı Sos', $itemNotes);
        $this->assertStringContainsString('Ayran', $itemNotes);
        $this->assertStringContainsString('+Ekstra Et', $itemNotes);
        $this->assertStringContainsString('-Soğan', $itemNotes);
    }

    /** @test */
    public function it_maps_order_statuses_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapOrderStatus');
        $method->setAccessible(true);

        $this->assertEquals('pending', $method->invoke($this->service, 'Created'));
        $this->assertEquals('preparing', $method->invoke($this->service, 'Picking'));
        $this->assertEquals('ready', $method->invoke($this->service, 'Invoiced'));
        $this->assertEquals('on_delivery', $method->invoke($this->service, 'Shipped'));
        $this->assertEquals('delivered', $method->invoke($this->service, 'Delivered'));
        $this->assertEquals('cancelled', $method->invoke($this->service, 'Cancelled'));
        $this->assertEquals('cancelled', $method->invoke($this->service, 'UnSupplied'));
    }

    /** @test */
    public function it_maps_to_platform_statuses_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapToPlatformStatus');
        $method->setAccessible(true);

        $this->assertEquals('Created', $method->invoke($this->service, 'pending'));
        $this->assertEquals('Picking', $method->invoke($this->service, 'preparing'));
        $this->assertEquals('Invoiced', $method->invoke($this->service, 'ready'));
        $this->assertEquals('Shipped', $method->invoke($this->service, 'on_delivery'));
        $this->assertEquals('Delivered', $method->invoke($this->service, 'delivered'));
        $this->assertEquals('Cancelled', $method->invoke($this->service, 'cancelled'));
    }

    /** @test */
    public function it_formats_customer_name_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatCustomerName');
        $method->setAccessible(true);

        // Test with address data
        $result = $method->invoke($this->service,
            ['firstName' => 'John', 'lastName' => 'Doe'],
            []
        );
        $this->assertEquals('John Doe', $result);

        // Test with customer data fallback
        $result = $method->invoke($this->service,
            [],
            ['firstName' => 'Jane', 'lastName' => 'Smith']
        );
        $this->assertEquals('Jane Smith', $result);

        // Test with default
        $result = $method->invoke($this->service, [], []);
        $this->assertEquals('Trendyol Müşterisi', $result);
    }
}
