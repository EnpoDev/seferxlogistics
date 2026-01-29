<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Http\Request;

class BayiZoneController extends Controller
{
    public function bolgelendirme()
    {
        $branches = \App\Models\Branch::all();
        $couriers = Courier::all();
        $zones = Zone::withCount('couriers')->get();

        // Unassigned couriers (not in any zone)
        $assignedCourierIds = \DB::table('courier_zone')->pluck('courier_id')->toArray();
        $unassignedCouriers = Courier::whereNotIn('id', $assignedCourierIds)->get();

        return view('bayi.bolgelendirme', compact('branches', 'couriers', 'zones', 'unassignedCouriers'));
    }

    public function zoneStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20',
            'coordinates' => 'required|array|min:4',
            'coordinates.*' => 'array|size:2',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'nullable|integer|min:1',
        ], [
            'coordinates.required' => 'Bölge koordinatları zorunludur. Lütfen haritada bölge çizin.',
            'coordinates.min' => 'Bölge için en az 4 nokta gereklidir.',
        ]);

        $zone = Zone::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bölge başarıyla oluşturuldu.',
            'zone' => $zone,
        ]);
    }

    public function zoneUpdate(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20',
            'coordinates' => 'nullable|array',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $zone->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bölge güncellendi.',
            'zone' => $zone,
        ]);
    }

    public function zoneDestroy(Zone $zone)
    {
        $zone->couriers()->detach();
        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bölge silindi.',
        ]);
    }

    public function zoneAssignCourier(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'is_primary' => 'nullable|boolean',
        ]);

        // Remove courier from other zones if setting as primary
        if ($request->boolean('is_primary')) {
            \DB::table('courier_zone')
                ->where('courier_id', $validated['courier_id'])
                ->update(['is_primary' => false]);
        }

        $zone->couriers()->syncWithoutDetaching([
            $validated['courier_id'] => ['is_primary' => $request->boolean('is_primary', false)]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kurye bölgeye atandı.',
        ]);
    }

    public function zoneRemoveCourier(Zone $zone, Courier $courier)
    {
        $zone->couriers()->detach($courier->id);

        return response()->json([
            'success' => true,
            'message' => 'Kurye bölgeden çıkarıldı.',
        ]);
    }

    public function zonesApi()
    {
        $zones = Zone::withCount('couriers')
            ->with('couriers:id,name,status')
            ->get();

        return response()->json($zones);
    }

    public function zoneDetails(Zone $zone)
    {
        // Get couriers in this zone
        $couriers = $zone->couriers()->get();
        $courierIds = $couriers->pluck('id')->toArray();

        // Get orders delivered by couriers in this zone (last 30 days)
        $orders = Order::whereIn('courier_id', $courierIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        // Calculate statistics
        $totalOrders = $orders->count();
        $deliveredOrders = $orders->where('status', 'delivered');
        $cancelledOrders = $orders->where('status', 'cancelled');

        // Revenue calculations
        $grossRevenue = $deliveredOrders->sum('total');
        $deliveryFees = $deliveredOrders->count() * ($zone->delivery_fee ?? 0);

        // Courier payments (estimated - delivery fee per order)
        $courierPayments = $deliveredOrders->count() * 15; // Assume 15 TL per delivery
        $netRevenue = $grossRevenue - $courierPayments;

        // Average delivery time
        $avgDeliveryTime = 0;
        $deliveredWithTime = $deliveredOrders->filter(fn($o) => $o->delivered_at && $o->created_at);
        if ($deliveredWithTime->count() > 0) {
            $totalMinutes = $deliveredWithTime->sum(fn($o) => $o->created_at->diffInMinutes($o->delivered_at));
            $avgDeliveryTime = round($totalMinutes / $deliveredWithTime->count());
        }

        // Completion rate
        $completionRate = $totalOrders > 0 ? round(($deliveredOrders->count() / $totalOrders) * 100, 1) : 0;

        // Courier performance
        $courierStats = $couriers->map(function ($courier) use ($orders) {
            $courierOrders = $orders->where('courier_id', $courier->id);
            $delivered = $courierOrders->where('status', 'delivered');

            return [
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone,
                'status' => $courier->status,
                'status_label' => $courier->getStatusLabel(),
                'total_orders' => $courierOrders->count(),
                'delivered_orders' => $delivered->count(),
                'cancelled_orders' => $courierOrders->where('status', 'cancelled')->count(),
                'total_earnings' => $delivered->count() * 15, // Estimated
                'avg_delivery_time' => $this->calculateCourierAvgTime($delivered),
            ];
        });

        // Recent orders
        $recentOrders = Order::whereIn('courier_id', $courierIds)
            ->with('courier:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'total' => $o->total,
                'status' => $o->status,
                'status_label' => $o->getStatusLabel(),
                'courier_name' => $o->courier?->name,
                'created_at' => $o->created_at->format('d.m.Y H:i'),
                'time_ago' => $o->created_at->diffForHumans(),
            ]);

        // Daily breakdown (last 7 days)
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayOrders = $orders->filter(fn($o) => $o->created_at->isSameDay($date));
            $dayDelivered = $dayOrders->where('status', 'delivered');

            $dailyStats[] = [
                'date' => $date->format('d.m'),
                'day' => $date->translatedFormat('l'),
                'orders' => $dayOrders->count(),
                'delivered' => $dayDelivered->count(),
                'revenue' => $dayDelivered->sum('total'),
            ];
        }

        return response()->json([
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'color' => $zone->color,
                'delivery_fee' => $zone->delivery_fee,
                'estimated_delivery_minutes' => $zone->estimated_delivery_minutes,
            ],
            'summary' => [
                'total_orders' => $totalOrders,
                'delivered_orders' => $deliveredOrders->count(),
                'cancelled_orders' => $cancelledOrders->count(),
                'pending_orders' => $orders->whereIn('status', ['pending', 'preparing', 'ready', 'on_delivery'])->count(),
                'gross_revenue' => $grossRevenue,
                'delivery_fees' => $deliveryFees,
                'courier_payments' => $courierPayments,
                'net_revenue' => $netRevenue,
                'avg_delivery_time' => $avgDeliveryTime,
                'completion_rate' => $completionRate,
                'courier_count' => $couriers->count(),
            ],
            'couriers' => $courierStats,
            'recent_orders' => $recentOrders,
            'daily_stats' => $dailyStats,
        ]);
    }

    private function calculateCourierAvgTime($deliveredOrders)
    {
        $withTime = $deliveredOrders->filter(fn($o) => $o->delivered_at && $o->created_at);
        if ($withTime->count() === 0) return 0;

        $totalMinutes = $withTime->sum(fn($o) => $o->created_at->diffInMinutes($o->delivered_at));
        return round($totalMinutes / $withTime->count());
    }
}
