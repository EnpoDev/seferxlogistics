<?php

namespace App\Http\Controllers\Kurye;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KuryeAppController extends Controller
{
    protected function courier(): Courier
    {
        return Auth::guard('courier')->user();
    }

    public function dashboard()
    {
        $courier = $this->courier();
        
        // Active orders assigned to this courier
        $activeOrders = Order::with(['items', 'restaurant', 'branch'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Today's stats
        $todayDelivered = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();
        
        $todayEarnings = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->sum('delivery_fee') ?? 0;
        
        return view('kurye.dashboard', compact('courier', 'activeOrders', 'todayDelivered', 'todayEarnings'));
    }

    public function orders()
    {
        $courier = $this->courier();
        
        $activeOrders = Order::with(['items', 'restaurant', 'branch'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('kurye.orders', compact('courier', 'activeOrders'));
    }

    public function orderDetail(Order $order)
    {
        $courier = $this->courier();
        
        // Ensure the order belongs to this courier
        if ($order->courier_id !== $courier->id) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }
        
        return view('kurye.order-detail', compact('courier', 'order'));
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $courier = $this->courier();
        
        if ($order->courier_id !== $courier->id) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        
        $request->validate([
            'status' => ['required', 'in:picked_up,on_way,delivered,cancelled'],
        ]);
        
        $newStatus = $request->status;
        
        // Status transition validation
        $allowedTransitions = [
            'assigned' => ['picked_up', 'cancelled'],
            'picked_up' => ['on_way', 'cancelled'],
            'on_way' => ['delivered', 'cancelled'],
        ];
        
        if (!isset($allowedTransitions[$order->status]) || 
            !in_array($newStatus, $allowedTransitions[$order->status])) {
            return response()->json([
                'success' => false, 
                'message' => 'Bu durum değişikliği geçerli değil.'
            ], 400);
        }
        
        $order->update(['status' => $newStatus]);
        
        // If delivered, update courier stats
        if ($newStatus === 'delivered') {
            $deliveryMinutes = $order->created_at->diffInMinutes(now());
            $courier->recordDelivery($deliveryMinutes);
            $courier->decrementActiveOrders();
        }
        
        // If cancelled, decrement active orders
        if ($newStatus === 'cancelled') {
            $courier->decrementActiveOrders();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Sipariş durumu güncellendi.',
            'new_status' => $newStatus,
            'status_label' => $order->getStatusLabel(),
        ]);
    }

    public function history(Request $request)
    {
        $courier = $this->courier();
        
        $orders = Order::where('courier_id', $courier->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
        
        return view('kurye.history', compact('courier', 'orders'));
    }

    public function profile()
    {
        $courier = $this->courier();
        
        // Monthly stats
        $monthlyDelivered = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
        
        $monthlyEarnings = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('delivery_fee') ?? 0;
        
        return view('kurye.profile', compact('courier', 'monthlyDelivered', 'monthlyEarnings'));
    }

    public function updateStatus(Request $request)
    {
        $courier = $this->courier();
        
        $request->validate([
            'status' => ['required', 'in:available,on_break,offline'],
        ]);
        
        $courier->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Durum güncellendi.',
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
        ]);
    }

    public function updateLocation(Request $request)
    {
        $courier = $this->courier();
        
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);
        
        $courier->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Konum güncellendi.',
        ]);
    }

    public function updateDeviceToken(Request $request)
    {
        $courier = $this->courier();
        
        $request->validate([
            'device_token' => ['required', 'string'],
        ]);
        
        $courier->update(['device_token' => $request->device_token]);
        
        return response()->json([
            'success' => true,
            'message' => 'Cihaz token\'ı güncellendi.',
        ]);
    }

    // Pool orders - unassigned orders that couriers can accept
    public function pool()
    {
        $courier = $this->courier();
        
        // Get orders in courier's zones that are not assigned
        $courierZoneIds = $courier->zones()->pluck('zones.id');
        
        $poolOrders = Order::where('status', 'ready')
            ->whereNull('courier_id')
            ->when($courierZoneIds->isNotEmpty(), function ($query) use ($courierZoneIds) {
                // If courier has zones, filter by those zones
                // This would require orders to have zone_id - implement based on your needs
            })
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();
        
        return view('kurye.pool', compact('courier', 'poolOrders'));
    }

    public function acceptOrder(Order $order)
    {
        $courier = $this->courier();
        
        // Check if order is still available
        if ($order->status !== 'ready' || $order->courier_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Bu sipariş artık mevcut değil.',
            ], 400);
        }
        
        // Check if courier can accept more orders
        if ($courier->active_orders_count >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimum sipariş sayısına ulaştınız.',
            ], 400);
        }
        
        $order->update([
            'courier_id' => $courier->id,
            'status' => 'assigned',
        ]);
        
        $courier->incrementActiveOrders();
        
        return response()->json([
            'success' => true,
            'message' => 'Sipariş kabul edildi.',
            'redirect' => route('kurye.order.detail', $order),
        ]);
    }
}

