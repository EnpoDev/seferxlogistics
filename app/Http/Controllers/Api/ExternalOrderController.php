<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\RestaurantConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalOrderController extends Controller
{
    /**
     * Create a new order from external platform (seferxyemek)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'external_order_id' => 'required|string',
            'external_restaurant_id' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.variations' => 'nullable|array',
            'subtotal' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'is_paid' => 'required|boolean',
            'notes' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'restaurant_working_hours' => 'nullable|array',
        ]);

        // Find the restaurant connection
        $connection = RestaurantConnection::where('external_restaurant_id', $validated['external_restaurant_id'])
            ->where('external_platform', 'seferxyemek')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json([
                'error' => 'restaurant_not_connected',
                'message' => 'Bu restoran lojistik sistemine bağlı değil.',
            ], 404);
        }

        // Authorization check - ensure authenticated user owns this connection
        if ($connection->user_id !== $request->user()->id) {
            Log::warning('Unauthorized order creation attempt', [
                'user_id' => $request->user()->id,
                'connection_user_id' => $connection->user_id,
                'external_restaurant_id' => $validated['external_restaurant_id'],
            ]);
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Bu restoran için sipariş oluşturma yetkiniz yok.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Check if order already exists (inside transaction with lock to prevent race condition)
            $existingOrder = Order::where('external_order_id', $validated['external_order_id'])
                ->where('platform', 'seferxyemek')
                ->lockForUpdate()
                ->first();

            if ($existingOrder) {
                DB::rollBack();
                return response()->json([
                    'error' => 'order_already_exists',
                    'message' => 'Bu sipariş zaten mevcut.',
                    'order' => $this->formatOrderResponse($existingOrder),
                ], 409);
            }
            // Find or create customer
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['customer_phone']],
                [
                    'name' => $validated['customer_name'],
                    'address' => $validated['customer_address'],
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                ]
            );

            // Create the order
            $order = Order::create([
                'order_number' => 'EXT-' . strtoupper(Str::random(8)),
                'tracking_token' => Str::uuid()->toString(),
                'user_id' => $connection->user_id,
                'restaurant_connection_id' => $connection->id,
                'customer_id' => $customer->id,
                'external_order_id' => $validated['external_order_id'],
                'platform' => 'seferxyemek',
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'subtotal' => $validated['subtotal'],
                'delivery_fee' => $validated['delivery_fee'] ?? 0,
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'is_paid' => $validated['is_paid'],
                'notes' => $validated['notes'] ?? null,
                'status' => $connection->auto_accept ? 'preparing' : 'pending',
                'scheduled_at' => $validated['scheduled_at'] ?? null,
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'notes' => $item['notes'] ?? null,
                    'variations' => $item['variations'] ?? null,
                ]);
            }

            // Note: Working hours are NOT updated from external orders for security
            // Restaurant owners should update working hours through the logistics panel directly

            // Update customer stats
            $customer->updateOrderStats();

            DB::commit();

            // Log the order creation
            Log::channel('orders')->info('External order created', [
                'order_id' => $order->id,
                'external_order_id' => $validated['external_order_id'],
                'platform' => 'seferxyemek',
                'connection_id' => $connection->id,
            ]);

            // Dispatch event for real-time updates
            event(new \App\Events\OrderCreated($order));

            return response()->json([
                'success' => true,
                'message' => 'Sipariş başarıyla oluşturuldu.',
                'order' => $this->formatOrderResponse($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create external order', [
                'error' => $e->getMessage(),
                'external_order_id' => $validated['external_order_id'],
            ]);

            return response()->json([
                'error' => 'order_creation_failed',
                'message' => 'Sipariş oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order status
     */
    public function show(Request $request, string $externalOrderId)
    {
        $user = $request->user();

        $order = Order::where('external_order_id', $externalOrderId)
            ->where('platform', 'seferxyemek')
            ->where('user_id', $user->id) // Authorization: only own orders
            ->with(['items', 'courier:id,name,phone'])
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Sipariş bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $this->formatOrderResponse($order),
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, string $externalOrderId)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,on_delivery,delivered,cancelled',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string',
        ]);

        $user = $request->user();

        $order = Order::where('external_order_id', $externalOrderId)
            ->where('platform', 'seferxyemek')
            ->where('user_id', $user->id) // Authorization: only own orders
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Sipariş bulunamadı.',
            ], 404);
        }

        $oldStatus = $order->status;
        $order->status = $validated['status'];

        if ($validated['status'] === 'cancelled') {
            $order->cancel_reason = $validated['cancel_reason'];
        }

        // Update timestamps based on status
        switch ($validated['status']) {
            case 'preparing':
                $order->accepted_at = now();
                break;
            case 'ready':
                $order->prepared_at = now();
                break;
            case 'on_delivery':
                $order->picked_up_at = now();
                break;
            case 'delivered':
                $order->delivered_at = now();
                break;
        }

        $order->save();

        // Dispatch event
        event(new \App\Events\OrderStatusUpdated($order, $oldStatus));

        return response()->json([
            'success' => true,
            'message' => 'Sipariş durumu güncellendi.',
            'order' => $this->formatOrderResponse($order),
        ]);
    }

    /**
     * Cancel order from external platform
     */
    public function cancel(Request $request, string $externalOrderId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();

        $order = Order::where('external_order_id', $externalOrderId)
            ->where('platform', 'seferxyemek')
            ->where('user_id', $user->id) // Authorization: only own orders
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Sipariş bulunamadı.',
            ], 404);
        }

        // Check if order can be cancelled
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'error' => 'cannot_cancel',
                'message' => 'Bu sipariş iptal edilemez.',
            ], 400);
        }

        $oldStatus = $order->status;
        $order->status = 'cancelled';
        $order->cancel_reason = $validated['reason'];
        $order->save();

        event(new \App\Events\OrderStatusUpdated($order, $oldStatus));

        return response()->json([
            'success' => true,
            'message' => 'Sipariş iptal edildi.',
            'order' => $this->formatOrderResponse($order),
        ]);
    }

    /**
     * List connected restaurants
     */
    public function restaurants(Request $request)
    {
        $user = $request->user();

        $connections = RestaurantConnection::where('user_id', $user->id)
            ->where('external_platform', 'seferxyemek')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'restaurants' => $connections->map(function ($connection) {
                return [
                    'connection_id' => $connection->id,
                    'external_restaurant_id' => $connection->external_restaurant_id,
                    'external_restaurant_name' => $connection->external_restaurant_name,
                    'auto_accept' => $connection->auto_accept,
                    'connected_at' => $connection->connected_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Update restaurant connection settings
     */
    public function updateRestaurantSettings(Request $request, int $connectionId)
    {
        $validated = $request->validate([
            'auto_accept' => 'sometimes|boolean',
            'webhook_url' => 'sometimes|nullable|url',
            'settings' => 'sometimes|array',
        ]);

        $user = $request->user();

        $connection = RestaurantConnection::where('id', $connectionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$connection) {
            return response()->json([
                'error' => 'connection_not_found',
                'message' => 'Bağlantı bulunamadı.',
            ], 404);
        }

        $connection->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ayarlar güncellendi.',
            'connection' => [
                'connection_id' => $connection->id,
                'external_restaurant_id' => $connection->external_restaurant_id,
                'auto_accept' => $connection->auto_accept,
                'webhook_url' => $connection->webhook_url,
                'settings' => $connection->settings,
            ],
        ]);
    }

    /**
     * Regenerate webhook secret for a connection
     */
    public function regenerateWebhookSecret(Request $request, int $connectionId)
    {
        $user = $request->user();

        $connection = RestaurantConnection::where('id', $connectionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$connection) {
            return response()->json([
                'error' => 'connection_not_found',
                'message' => 'Bağlantı bulunamadı.',
            ], 404);
        }

        // Generate new webhook secret
        $newSecret = $connection->generateWebhookSecret();

        Log::info('Webhook secret regenerated', [
            'connection_id' => $connection->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook secret yenilendi.',
            'webhook_secret' => $newSecret,
            'connection_id' => $connection->id,
        ]);
    }

    /**
     * Get current webhook secret for a connection
     */
    public function getWebhookSecret(Request $request, int $connectionId)
    {
        $user = $request->user();

        $connection = RestaurantConnection::where('id', $connectionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$connection) {
            return response()->json([
                'error' => 'connection_not_found',
                'message' => 'Bağlantı bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'webhook_secret' => $connection->webhook_secret,
            'connection_id' => $connection->id,
        ]);
    }

    /**
     * Format order for API response
     */
    protected function formatOrderResponse(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'tracking_token' => $order->tracking_token,
            'external_order_id' => $order->external_order_id,
            'status' => $order->status,
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'address' => $order->customer_address,
                'lat' => $order->lat,
                'lng' => $order->lng,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->product_name ?? $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'total_price' => $item->total,
                ];
            }),
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'is_paid' => $order->is_paid,
            'notes' => $order->notes,
            'courier' => $order->courier ? [
                'id' => $order->courier->id,
                'name' => $order->courier->name,
                'phone' => $order->courier->phone,
            ] : null,
            'timestamps' => [
                'created_at' => $order->created_at?->toIso8601String(),
                'accepted_at' => $order->accepted_at?->toIso8601String(),
                'prepared_at' => $order->prepared_at?->toIso8601String(),
                'picked_up_at' => $order->picked_up_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
            ],
            'cancel_reason' => $order->cancel_reason,
            'tracking_url' => route('tracking.show', $order->tracking_token),
        ];
    }
}
