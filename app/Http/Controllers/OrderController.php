<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Courier;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\OrderItem;
use App\Services\CourierAssignmentService;
use App\Services\CustomerNotificationService;
use App\Services\PoolService;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private CourierAssignmentService $courierAssignmentService,
        private PoolService $poolService,
        private CustomerNotificationService $customerNotificationService
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['courier', 'branch', 'items', 'customer', 'restaurant'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }

        // Search by order number or customer name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20);

        return view('pages.siparis.liste', compact('orders'));
    }

    public function history()
    {
        $orders = Order::with(['courier', 'branch', 'items', 'customer'])
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.siparis.gecmis', compact('orders'));
    }

    public function cancelled()
    {
        $cancelledOrders = Order::with(['courier', 'branch', 'items'])
            ->where('status', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.siparis.iptal', compact('cancelledOrders'));
    }

    public function statistics()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'preparing_orders' => Order::where('status', 'preparing')->count(),
            'on_delivery_orders' => Order::where('status', 'on_delivery')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'returned_orders' => Order::where('status', 'returned')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total') ?? 0,
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())->where('status', 'delivered')->sum('total') ?? 0,
            'top_products' => [],
            'daily_labels' => ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz'],
            'daily_orders' => [0, 0, 0, 0, 0, 0, 0],
            'hourly_labels' => ['12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'],
            'hourly_orders' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        ];

        return view('pages.siparis.istatistik', compact('stats'));
    }

    public function create(Request $request)
    {
        // Get categories with their products grouped by restaurant
        $categories = Category::with(['products' => function ($query) {
                $query->where('is_active', true)
                      ->where('in_stock', true)
                      ->with('restaurant');
            }])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
        
        // Get all active products grouped by category
        $products = Product::where('is_active', true)
            ->where('in_stock', true)
            ->with(['category', 'restaurant'])
            ->get()
            ->groupBy('category_id');
        
        $couriers = Courier::whereIn('status', ['available', 'active'])
            ->orderBy('name')
            ->get();
        
        $branches = Branch::where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();

        $restaurants = Restaurant::where('is_active', true)
            ->orderBy('name')
            ->get();

        // If customer_id is provided, load the customer
        $customer = null;
        if ($request->has('customer_id')) {
            $customer = Customer::with('addresses')->find($request->customer_id);
        }

        return view('pages.siparis.create', compact('categories', 'products', 'couriers', 'branches', 'restaurants', 'customer'));
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        // Create or update customer
        $phone = preg_replace('/[^0-9]/', '', $validated['customer_phone']);
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'],
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
            ]
        );

        // Update customer info if already exists
        if (!$customer->wasRecentlyCreated) {
            $customer->update([
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'],
                'lat' => $validated['lat'] ?? $customer->lat,
                'lng' => $validated['lng'] ?? $customer->lng,
            ]);
        }

        // Generate order number
        $orderNumber = Order::generateOrderNumber();

        // Calculate totals
        $subtotal = 0;
        $orderItems = [];
        
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $itemTotal = $product->getCurrentPrice() * $item['quantity'];
            $subtotal += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->getCurrentPrice(),
                'quantity' => $item['quantity'],
                'total' => $itemTotal,
            ];
        }

        $total = $subtotal + $validated['delivery_fee'];

        // Auto-assign courier if requested
        $courierId = $validated['courier_id'] ?? null;
        if ($request->boolean('auto_assign_courier') && !$courierId) {
            $assignedCourier = $this->courierAssignmentService->findBestCourier(
                $validated['lat'] ?? null,
                $validated['lng'] ?? null
            );
            $courierId = $assignedCourier?->id;
        }

        // Validate courier ownership - ensure courier belongs to branch's bayi
        if ($courierId && isset($validated['branch_id'])) {
            $courierToValidate = Courier::find($courierId);
            $branchToValidate = Branch::find($validated['branch_id']);

            if ($courierToValidate && $branchToValidate) {
                $branchOwner = $branchToValidate->getOwnerUser();

                if ($branchOwner) {
                    // Determine the bayi user ID (owner might be isletme or bayi)
                    $bayiUserId = $branchOwner->role === 'isletme'
                        ? $branchOwner->parent_id
                        : $branchOwner->id;

                    if ($courierToValidate->user_id !== $bayiUserId) {
                        abort(403, 'Bu kuryeyi kullanamazsınız.');
                    }
                }
            }
        }

        // Create order
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'courier_id' => $courierId,
            'branch_id' => $validated['branch_id'] ?? null,
            'restaurant_id' => $validated['restaurant_id'] ?? null,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $phone,
            'customer_address' => $validated['customer_address'],
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'subtotal' => $subtotal,
            'delivery_fee' => $validated['delivery_fee'],
            'total' => $total,
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create order items
        foreach ($orderItems as $item) {
            $order->items()->create($item);
        }

        // Update customer stats
        $customer->updateOrderStats();

        // Update courier active orders count
        if ($courierId) {
            $courier = Courier::find($courierId);
            $courier?->incrementActiveOrders();
        }

        // Broadcast order created event
        broadcast(new OrderCreated($order))->toOthers();

        return redirect()
            ->route('siparis.liste')
            ->with('success', __('messages.success.order_created'));
    }

    public function edit(Order $order)
    {
        $order->load(['items.product', 'courier', 'branch', 'customer', 'restaurant']);
        
        $categories = Category::with(['products' => function ($query) {
                $query->where('is_active', true)->with('restaurant');
            }])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $products = Product::where('is_active', true)
            ->with(['category', 'restaurant'])
            ->get()
            ->groupBy('category_id');
        
        $couriers = Courier::orderBy('name')->get();
        
        $branches = Branch::where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();

        $restaurants = Restaurant::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.siparis.edit', compact('order', 'categories', 'products', 'couriers', 'branches', 'restaurants'));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validated = $request->validated();

        // Calculate totals
        $subtotal = 0;
        $newItems = [];
        
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $itemTotal = $product->getCurrentPrice() * $item['quantity'];
            $subtotal += $itemTotal;
            
            $newItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->getCurrentPrice(),
                'quantity' => $item['quantity'],
                'total' => $itemTotal,
            ];
        }

        $total = $subtotal + $validated['delivery_fee'];

        // Update timestamps based on status
        $timestamps = [];
        if ($validated['status'] === 'preparing' && !$order->accepted_at) {
            $timestamps['accepted_at'] = now();
        } elseif ($validated['status'] === 'ready' && !$order->prepared_at) {
            $timestamps['prepared_at'] = now();
        } elseif ($validated['status'] === 'on_delivery' && !$order->picked_up_at) {
            $timestamps['picked_up_at'] = now();
        } elseif ($validated['status'] === 'delivered' && !$order->delivered_at) {
            $timestamps['delivered_at'] = now();

            // Record delivery for courier
            if ($order->courier_id) {
                $deliveryMinutes = $order->created_at->diffInMinutes(now());
                $order->courier?->recordDelivery($deliveryMinutes);
                $order->courier?->decrementActiveOrders();

                // Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
                $order->updateCourierCashBalance();
            }
        } elseif ($validated['status'] === 'cancelled' && !$order->cancelled_at) {
            $timestamps['cancelled_at'] = now();
            
            // Update courier active orders
            if ($order->courier_id) {
                $order->courier?->decrementActiveOrders();
            }
        }

        // Track courier change
        $oldCourierId = $order->courier_id;
        $newCourierId = $validated['courier_id'] ?? null;

        // Update order
        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => preg_replace('/[^0-9]/', '', $validated['customer_phone']),
            'customer_address' => $validated['customer_address'],
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'courier_id' => $newCourierId,
            'branch_id' => $validated['branch_id'] ?? null,
            'restaurant_id' => $validated['restaurant_id'] ?? null,
            'subtotal' => $subtotal,
            'delivery_fee' => $validated['delivery_fee'],
            'total' => $total,
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ] + $timestamps);

        // Handle courier change
        if ($oldCourierId !== $newCourierId && !in_array($validated['status'], ['delivered', 'cancelled'])) {
            if ($oldCourierId) {
                Courier::find($oldCourierId)?->decrementActiveOrders();
            }
            if ($newCourierId) {
                Courier::find($newCourierId)?->incrementActiveOrders();
            }
        }

        // Add to pool if status is ready and no courier assigned
        if ($validated['status'] === 'ready' && !$newCourierId) {
            if ($this->poolService->isPoolEnabled($order->branch_id) && !$order->pool_entered_at) {
                $this->poolService->addToPool($order);
            }
        }

        // Update order items - delete old and create new
        $order->items()->delete();
        foreach ($newItems as $item) {
            $order->items()->create($item);
        }

        // Update customer stats
        $order->updateCustomerStats();

        return redirect()
            ->route('siparis.liste')
            ->with('success', __('messages.success.order_updated'));
    }

    public function destroy(Order $order)
    {
        // Only allow deletion of pending orders or cancelled orders
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            return redirect()
                ->route('siparis.liste')
                ->with('error', __('messages.error.order_cannot_delete'));
        }

        // Update courier active orders
        if ($order->courier_id && $order->status === 'pending') {
            $order->courier?->decrementActiveOrders();
        }

        $order->items()->delete();
        $order->delete();

        return redirect()
            ->route('siparis.liste')
            ->with('success', __('messages.success.order_deleted'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $validated = $request->validated();

        $oldStatus = $order->status;

        $timestamps = [];
        if ($validated['status'] === 'preparing' && !$order->accepted_at) {
            $timestamps['accepted_at'] = now();
        } elseif ($validated['status'] === 'ready' && !$order->prepared_at) {
            $timestamps['prepared_at'] = now();
        } elseif ($validated['status'] === 'on_delivery' && !$order->picked_up_at) {
            $timestamps['picked_up_at'] = now();
            // Leave pool if was in pool
            if ($order->pool_entered_at) {
                $timestamps['pool_entered_at'] = null;
            }
        } elseif ($validated['status'] === 'delivered' && !$order->delivered_at) {
            $timestamps['delivered_at'] = now();

            if ($order->courier_id) {
                $deliveryMinutes = $order->created_at->diffInMinutes(now());
                $order->courier?->recordDelivery($deliveryMinutes);
                $order->courier?->decrementActiveOrders();

                // Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
                $order->updateCourierCashBalance();
            }
        } elseif ($validated['status'] === 'cancelled' && !$order->cancelled_at) {
            $timestamps['cancelled_at'] = now();
            // Leave pool if was in pool
            if ($order->pool_entered_at) {
                $timestamps['pool_entered_at'] = null;
            }

            if ($order->courier_id) {
                $order->courier?->decrementActiveOrders();
            }
        }

        $order->update(['status' => $validated['status']] + $timestamps);

        // Add to pool if status is ready and no courier assigned
        if ($validated['status'] === 'ready' && !$order->courier_id) {
            if ($this->poolService->isPoolEnabled($order->branch_id)) {
                $this->poolService->addToPool($order);
            }
        }

        // Update customer stats
        $order->updateCustomerStats();

        // Broadcast status update event
        broadcast(new OrderStatusUpdated($order, $oldStatus))->toOthers();

        // Müşteriye bildirim gönder
        try {
            $this->customerNotificationService->sendStatusNotification($order, $validated['status']);
        } catch (\Exception $e) {
            \Log::error(__('messages.error.customer_notification_failed'), ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.success.order_status_updated'),
        ]);
    }
}
