<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Http\Request;

class BayiController extends Controller
{
    public function harita()
    {
        $couriers = Courier::with(['currentOrder', 'orders' => function($q) {
            $q->whereDate('created_at', today())
              ->whereNotIn('status', ['delivered', 'cancelled']);
        }])->get();
        
        $activeOrders = Order::whereNotIn('status', ['delivered', 'cancelled'])->count();
        $newOrders = Order::where('status', 'pending')->count();
        $poolOrders = Order::where('status', 'ready')->whereNull('courier_id')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->whereDate('created_at', today())->count();
        
        return view('bayi.harita', compact('couriers', 'activeOrders', 'newOrders', 'poolOrders', 'cancelledOrders'));
    }

    public function kuryelerim(Request $request)
    {
        $query = Courier::withCount([
            'orders as today_deliveries' => function($q) {
                $q->whereDate('created_at', today())
                  ->where('status', 'delivered');
            },
            'orders as total_deliveries' => function($q) {
                $q->where('status', 'delivered');
            }
        ]);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tc_no', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%");
            });
        }

        $couriers = $query->orderBy('name')->get();
        
        return view('bayi.kuryelerim', compact('couriers'));
    }

    public function kuryeEkle()
    {
        return view('bayi.kurye-ekle');
    }

    public function kuryeDuzenle(Courier $courier)
    {
        return view('bayi.kurye-duzenle', compact('courier'));
    }

    public function isletmelerim(Request $request)
    {
        // Only show root branches (parent_id is null)
        $query = \App\Models\Branch::whereNull('parent_id')
            ->orderBy('is_main', 'desc')
            ->orderBy('created_at', 'desc');
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $branches = $query->withCount('children')->get();
        $businessInfo = \App\Models\BusinessInfo::first();
        
        return view('bayi.isletmelerim', compact('branches', 'businessInfo'));
    }

    public function isletmeEkle(Request $request)
    {
        $parent_id = $request->get('parent_id');
        $parent = null;
        if ($parent_id) {
            $parent = \App\Models\Branch::find($parent_id);
        }
        return view('bayi.isletme-ekle', compact('parent'));
    }

    public function isletmeKaydet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'required|in:active,passive',
            'parent_id' => 'nullable|exists:branches,id',
            'is_main' => 'sometimes|boolean'
        ]);

        $validated['is_active'] = $request->status === 'active';
        $validated['is_main'] = $request->has('is_main') && !$request->parent_id; // Only root can be main? Or sub can be main too? Let's assume only one main globally for now, or per user. If per hierarchy, maybe check parent.

        if ($validated['is_main']) {
            \App\Models\Branch::where('is_main', true)->update(['is_main' => false]);
        }

        $branch = \App\Models\Branch::create($validated);

        if ($branch->parent_id) {
            return redirect()->route('bayi.isletme-detay', $branch->parent_id)->with('success', 'Şube başarıyla eklendi.');
        }

        return redirect()->route('bayi.isletmelerim')->with('success', 'İşletme başarıyla eklendi.');
    }

    public function isletmeDetay(\App\Models\Branch $branch)
    {
        // Check if branch is a sub-branch? If so, maybe redirect to parent or show it.
        // For now, let's just show details and children.
        $children = $branch->children()->orderBy('created_at', 'desc')->get();
        return view('bayi.isletme-detay', compact('branch', 'children'));
    }

    public function isletmeDuzenle(\App\Models\Branch $branch)
    {
        return view('bayi.isletme-duzenle', compact('branch'));
    }

    public function isletmeGuncelle(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'required|in:active,passive',
            'is_main' => 'sometimes|boolean'
        ]);

        $validated['is_active'] = $request->status === 'active';
        $validated['is_main'] = $request->has('is_main') && !$branch->parent_id;

        if ($validated['is_main'] && !$branch->is_main) {
             \App\Models\Branch::where('is_main', true)->update(['is_main' => false]);
        }

        $branch->update($validated);

        if ($branch->parent_id) {
            return redirect()->route('bayi.isletme-detay', $branch->parent_id)->with('success', 'Şube başarıyla güncellendi.');
        }

        return redirect()->route('bayi.isletmelerim')->with('success', 'İşletme başarıyla güncellendi.');
    }

    public function isletmeSil(\App\Models\Branch $branch)
    {
        $parentId = $branch->parent_id;
        $branch->delete();
        
        if ($parentId) {
            return redirect()->route('bayi.isletme-detay', $parentId)->with('success', 'Şube başarıyla silindi.');
        }
        
        return redirect()->route('bayi.isletmelerim')->with('success', 'İşletme başarıyla silindi.');
    }

    public function vardiyaSaatleri(Request $request)
    {
        $query = Courier::orderBy('name');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tc_no', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%");
            });
        }

        $couriers = $query->paginate(50);
        $businessInfo = \App\Models\BusinessInfo::first();
        
        return view('bayi.vardiya-saatleri', compact('couriers', 'businessInfo'));
    }

    public function vardiyaGuncelle(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'day' => 'required|integer|min:0|max:6', // 0=Mon, 6=Sun or 0=Sun, 6=Sat? Let's assume 0=Monday based on view loop
            'hours' => 'nullable|string|max:50'
        ]);

        $shifts = $courier->shifts ?? [];
        $shifts[$validated['day']] = $validated['hours'];
        
        // Ensure array is saved properly even if sparse, but json_encode handles it.
        // If we want to keep it clean, maybe keyed by day name?
        // View loop uses $i=0..6. 
        // Let's use keys '0', '1', etc.
        
        $courier->shifts = $shifts;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaVarsayilanKaydet(Request $request)
    {
        $validated = $request->validate([
            'default_shifts' => 'nullable|array',
            'auto_assign_shifts' => 'boolean'
        ]);

        $businessInfo = \App\Models\BusinessInfo::first();
        if (!$businessInfo) {
            // Should not happen usually, but handle it or create one?
            // Assuming one exists as per current logic
            $businessInfo = \App\Models\BusinessInfo::create([
                'name' => 'Default Business', 
                'phone' => '', 
                'email' => '', 
                'address' => ''
            ]);
        }

        $businessInfo->update([
            'default_shifts' => $request->default_shifts,
            'auto_assign_shifts' => $request->has('auto_assign_shifts') // checkbox sends 'on' or nothing, usually handled by boolean validation if present, but let's be safe
        ]);
        
        // If request is JSON (ajax) return json, else redirect
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Varsayılan vardiya ayarları güncellendi.');
    }

    public function vardiyaTopluGuncelle(Request $request)
    {
        $validated = $request->validate([
            'shifts' => 'required|array',
            'courier_ids' => 'nullable|array', // If null/empty, maybe update all? Or none? Let's say all visible/filtered? For safety, require ids or a flag 'all'
            'apply_to_all' => 'boolean'
        ]);

        $query = Courier::query();

        if ($request->apply_to_all) {
            // Apply to all filtered couriers? Or absolutely all?
            // "Tüm kuryeleri çek ve ... güncelleyebilmemizi sağla"
            // Usually bulk action applies to selected checkboxes or all.
            // Let's assume the user selects via checkboxes or chooses "Update All".
            // If we implement search, "apply to all" might mean "all matching search".
            // For simplicity, let's implement "apply to selected ids".
        } else {
             if (empty($request->courier_ids)) {
                 return back()->with('error', 'Lütfen en az bir kurye seçin.');
             }
             $query->whereIn('id', $request->courier_ids);
        }

        // We only want to update specific days if provided? Or replace the whole schedule?
        // The prompt says "Toplu güncelle". Usually replaces the schedule.
        // But what if I only want to update Monday?
        // Let's assume the modal provides a full week schedule input, similar to default settings.
        // And we overwrite the shifts.
        
        // Wait, merging might be better if the user only fills one day in the bulk modal?
        // Let's assume overwrite for now as it's simpler and safer than partial merges which might be confusing.
        // Actually, merging is better UX: "Update Monday for everyone".
        // Let's see how we implement the modal. If the modal has all 7 days inputs, blank means "no change" or "clear"?
        // Better: The modal has inputs for 7 days. If an input is empty, do we clear it or keep existing?
        // Let's add a "clear" option or assume non-empty inputs overwrite.
        
        // Implementation:
        // Load couriers.
        // For each courier, update shifts.
        // If we want to support "only update Monday", we need to merge.
        
        $shiftsToUpdate = $request->shifts; // ['0' => '09:00-18:00', '1' => null, ...]
        
        // Filter out nulls/empty strings if we want to "keep existing".
        // If the user wants to clear a day, maybe they send specific value?
        // For simplicity: The modal will show "Leave empty to keep current".
        // If they want to clear, maybe they type "OFF"?
        
        // Let's just update provided non-null values.
        
        $couriers = $query->get();
        foreach ($couriers as $courier) {
            $currentShifts = $courier->shifts ?? [];
            foreach ($shiftsToUpdate as $day => $time) {
                if (!is_null($time) && $time !== '') {
                    $currentShifts[$day] = $time;
                }
            }
            $courier->shifts = $currentShifts;
            $courier->save();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Seçilen kuryelerin vardiya saatleri güncellendi.');
    }

    public function kullaniciYonetimi()
    {
        $users = \App\Models\User::orderBy('name')->paginate(20);
        return view('bayi.kullanici-yonetimi', compact('users'));
    }

    public function istatistik()
    {
        // Calculate real average delivery time
        $deliveredOrders = Order::whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->get();
        
        $avgDeliveryTime = 0;
        if ($deliveredOrders->count() > 0) {
            $totalMinutes = $deliveredOrders->sum(function ($order) {
                return $order->created_at->diffInMinutes($order->delivered_at);
            });
            $avgDeliveryTime = round($totalMinutes / $deliveredOrders->count());
        }
        
        // Calculate completion rate
        $totalOrders = Order::whereDate('created_at', '>=', now()->subDays(30))->count();
        $completedOrders = Order::whereDate('created_at', '>=', now()->subDays(30))
            ->where('status', 'delivered')
            ->count();
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;
        
        $stats = [
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->where('status', 'delivered')->sum('total'),
            'active_couriers' => Courier::whereIn('status', ['available', 'busy'])->count(),
            'avg_delivery_time' => $avgDeliveryTime ?: 0,
            'completion_rate' => $completionRate,
            'pending_orders' => Order::where('status', 'pending')->count(),
            'on_delivery_orders' => Order::where('status', 'on_delivery')->count(),
        ];
        
        return view('bayi.istatistik', compact('stats'));
    }

    public function gelismisIstatistik()
    {
        // Last 7 days stats
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $dailyOrders = [];
        $dailyRevenue = [];

        foreach ($dates as $date) {
            $orders = Order::whereDate('created_at', $date)->get();
            $dailyOrders[] = $orders->count();
            $dailyRevenue[] = $orders->where('status', 'delivered')->sum('total');
        }

        $stats = [
            'dates' => $dates->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'orders' => $dailyOrders,
            'revenue' => $dailyRevenue,
            'top_couriers' => Courier::withCount('orders')->orderBy('orders_count', 'desc')->take(5)->get(),
            'top_branches' => \App\Models\Branch::withCount('orders')->orderBy('orders_count', 'desc')->take(5)->get(),
        ];

        return view('bayi.gelismis-istatistik', compact('stats'));
    }

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
            'coordinates' => 'nullable|array',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'nullable|integer|min:1',
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

    public function kuryeOdemeleri()
    {
        $couriers = Courier::with(['orders' => function($q) {
            $q->where('status', 'delivered')
              ->whereMonth('delivered_at', now()->month);
        }])->get();
        
        return view('bayi.odemeler.kurye', compact('couriers'));
    }

    public function isletmeOdemeleri()
    {
        $branches = \App\Models\Branch::with(['orders' => function($q) {
            $q->where('status', 'delivered')
              ->whereMonth('created_at', now()->month);
        }])->get();

        return view('bayi.odemeler.isletme', compact('branches'));
    }

    public function gecmisSiparisler()
    {
        $orders = Order::with(['courier', 'branch'])
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('bayi.siparisler.gecmis', compact('orders'));
    }

    public function bedelsizIstekler()
    {
        $orders = Order::with(['courier', 'branch'])
            ->where('total', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bayi.siparisler.bedelsiz', compact('orders'));
    }

    public function ayarlarGenel()
    {
        $user = auth()->user();
        return view('bayi.ayarlar.genel', compact('user'));
    }

    public function ayarlarKurye()
    {
        return view('bayi.ayarlar.kurye');
    }

    public function ayarlarUygulama()
    {
        return view('bayi.ayarlar.uygulama');
    }

    public function ayarlarHavuz()
    {
        return view('bayi.ayarlar.havuz');
    }

    public function ayarlarBildirim()
    {
        return view('bayi.ayarlar.bildirim');
    }

    public function tema()
    {
        return view('bayi.tema');
    }

    public function yardim()
    {
        return view('bayi.yardim');
    }
}

