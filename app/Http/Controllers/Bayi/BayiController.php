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
        // Ensure branch has settings
        if (!$branch->settings) {
            $branch->settings()->create([]);
        }

        // Load relationships
        $branch->load(['settings', 'pricingPolicies.rules']);

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
            'day' => 'required|integer|min:0|max:6',
            'hours' => 'nullable|string|max:50',
            'break_duration' => 'nullable|integer|min:0',
            'break_parts' => 'nullable|integer|min:0',
        ]);

        $shifts = $courier->shifts ?? [];
        $shifts[$validated['day']] = $validated['hours'];

        $breakDurations = $courier->break_durations ?? [];
        if ($request->filled('break_duration') && $request->filled('break_parts')) {
            $breakDurations[$validated['day']] = [
                'duration' => $validated['break_duration'],
                'parts' => $validated['break_parts'],
            ];
        }

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaVarsayilanKaydet(Request $request)
    {
        $validated = $request->validate([
            'default_shifts' => 'nullable|array',
            'default_break_duration' => 'nullable|integer|min:0',
            'default_break_parts' => 'nullable|integer|min:0',
            'auto_assign_shifts' => 'boolean'
        ]);

        $businessInfo = \App\Models\BusinessInfo::first();
        if (!$businessInfo) {
            $businessInfo = \App\Models\BusinessInfo::create([
                'name' => 'Default Business',
                'phone' => '',
                'email' => '',
                'address' => ''
            ]);
        }

        $businessInfo->update([
            'default_shifts' => $request->default_shifts,
            'default_break_duration' => $request->default_break_duration ?? 60,
            'default_break_parts' => $request->default_break_parts ?? 2,
            'auto_assign_shifts' => $request->has('auto_assign_shifts')
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Varsayılan vardiya ayarları güncellendi.');
    }

    public function vardiyaTopluGuncelle(Request $request)
    {
        $validated = $request->validate([
            'shifts' => 'required|array',
            'break_durations' => 'nullable|array',
            'courier_ids' => 'nullable|array',
            'apply_to_all' => 'boolean'
        ]);

        $query = Courier::query();

        if ($request->apply_to_all) {
            // Apply to all couriers
        } else {
             if (empty($request->courier_ids)) {
                 return back()->with('error', 'Lütfen en az bir kurye seçin.');
             }
             $query->whereIn('id', $request->courier_ids);
        }

        $shiftsToUpdate = $request->shifts;
        $breaksToUpdate = $request->break_durations ?? [];

        $couriers = $query->get();
        foreach ($couriers as $courier) {
            $currentShifts = $courier->shifts ?? [];
            $currentBreaks = $courier->break_durations ?? [];

            foreach ($shiftsToUpdate as $day => $time) {
                if (!is_null($time) && $time !== '') {
                    $currentShifts[$day] = $time;
                }
            }

            foreach ($breaksToUpdate as $day => $breakData) {
                if (isset($breakData['duration']) && isset($breakData['parts'])) {
                    $currentBreaks[$day] = [
                        'duration' => $breakData['duration'],
                        'parts' => $breakData['parts'],
                    ];
                }
            }

            $courier->shifts = $currentShifts;
            $courier->break_durations = $currentBreaks;
            $courier->save();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Seçilen kuryelerin vardiya saatleri güncellendi.');
    }

    public function vardiyaSil(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'day' => 'required|integer|min:0|max:6',
        ]);

        $shifts = $courier->shifts ?? [];
        $breakDurations = $courier->break_durations ?? [];

        unset($shifts[$validated['day']]);
        unset($breakDurations[$validated['day']]);

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaKopyala(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'source_day' => 'required|integer|min:0|max:6',
            'target_days' => 'required|array',
            'target_days.*' => 'integer|min:0|max:6',
        ]);

        $shifts = $courier->shifts ?? [];
        $breakDurations = $courier->break_durations ?? [];

        $sourceShift = $shifts[$validated['source_day']] ?? null;
        $sourceBreak = $breakDurations[$validated['source_day']] ?? null;

        foreach ($validated['target_days'] as $targetDay) {
            if ($sourceShift) {
                $shifts[$targetDay] = $sourceShift;
            }
            if ($sourceBreak) {
                $breakDurations[$targetDay] = $sourceBreak;
            }
        }

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaSablonUygula(Request $request, Courier $courier)
    {
        $businessInfo = \App\Models\BusinessInfo::first();

        if (!$businessInfo || !$businessInfo->default_shifts) {
            return response()->json(['success' => false, 'message' => 'Varsayılan şablon bulunamadı.'], 404);
        }

        $courier->shifts = $businessInfo->default_shifts;

        // Apply default breaks to all days
        $breakDurations = [];
        for ($i = 0; $i < 7; $i++) {
            if (isset($businessInfo->default_shifts[$i])) {
                $breakDurations[$i] = [
                    'duration' => $businessInfo->default_break_duration ?? 60,
                    'parts' => $businessInfo->default_break_parts ?? 2,
                ];
            }
        }

        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
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

    public function gelismisIstatistik(Request $request)
    {
        // Date range filtering
        $period = $request->get('period', '7days');

        [$startDate, $endDate] = $this->getDateRange($period);

        // Initialize statistics service
        $statsService = new \App\Services\AdvancedStatisticsService($startDate, $endDate);

        // Get all statistics
        $stats = $statsService->getAllStatistics();
        $topPerformers = $statsService->getTopPerformers();

        // Merge top performers
        $stats['top_couriers'] = $topPerformers['top_couriers'];
        $stats['top_branches'] = $topPerformers['top_branches'];

        // Add period info
        $stats['period'] = $period;
        $stats['start_date'] = $startDate->format('d.m.Y');
        $stats['end_date'] = $endDate->format('d.m.Y');

        return view('bayi.gelismis-istatistik', compact('stats'));
    }

    /**
     * Helper method to get date range based on period
     */
    private function getDateRange(string $period): array
    {
        $endDate = now();

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(7),
        };

        if ($period === 'last_month') {
            $endDate = now()->subMonth()->endOfMonth();
        }

        return [$startDate, $endDate];
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

    public function kuryeOdemeStore(Request $request)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $courier = Courier::findOrFail($validated['courier_id']);

        // Create cash transaction (avans verilen)
        $transaction = \App\Models\CashTransaction::create([
            'courier_id' => $courier->id,
            'type' => \App\Models\CashTransaction::TYPE_ADVANCE_GIVEN,
            'amount' => $validated['amount'],
            'status' => \App\Models\CashTransaction::STATUS_COMPLETED,
            'notes' => $validated['notes'] ?? "Aylik hakedis odemesi",
            'created_by' => auth()->id(),
        ]);

        // Apply to balance
        $transaction->applyToBalance();

        return response()->json([
            'success' => true,
            'message' => $courier->name . ' icin ' . number_format($validated['amount'], 2) . ' TL odeme kaydedildi.',
        ]);
    }

    public function isletmeOdemeleri()
    {
        $branches = \App\Models\Branch::with(['orders' => function($q) {
            $q->where('status', 'delivered')
              ->whereMonth('created_at', now()->month);
        }])->get();

        return view('bayi.odemeler.isletme', compact('branches'));
    }

    public function isletmeOdemeStore(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create transaction record
        \App\Models\Transaction::create([
            'user_id' => auth()->id(),
            'branch_id' => $branch->id,
            'type' => \App\Models\Transaction::TYPE_COMMISSION,
            'amount' => $validated['amount'],
            'currency' => 'TRY',
            'status' => \App\Models\Transaction::STATUS_COMPLETED,
            'description' => $validated['notes'] ?? "Aylik komisyon tahsilati",
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $branch->name . ' icin ' . number_format($validated['amount'], 2) . ' TL tahsilat kaydedildi.',
        ]);
    }

    public function isletmeOdemeRapor(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $branches = \App\Models\Branch::with(['orders' => function($q) use ($startDate, $endDate) {
            $q->where('status', 'delivered')
              ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }])->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.isletme-odemeler-rapor', [
            'branches' => $branches,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $pdf->download('isletme-odemeler-rapor-' . now()->format('Y-m-d') . '.pdf');
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

    public function bedelsizApprove(Order $order)
    {
        $order->update(['status' => 'approved']);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Istek onaylandi.']);
        }

        return back()->with('success', 'Istek onaylandi.');
    }

    public function bedelsizReject(Order $order)
    {
        $order->update(['status' => 'cancelled']);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Istek reddedildi.']);
        }

        return back()->with('success', 'Istek reddedildi.');
    }

    public function ayarlarGenel()
    {
        $user = auth()->user();
        return view('bayi.ayarlar.genel', compact('user'));
    }

    public function updateGenel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $user->update($validated);

        return back()->with('success', 'Genel ayarlar basariyla guncellendi.');
    }

    public function ayarlarKurye()
    {
        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        $settings = $branch ? \App\Models\BranchSetting::getOrCreateForBranch($branch->id) : null;
        return view('bayi.ayarlar.kurye', compact('settings'));
    }

    public function updateKurye(Request $request)
    {
        $validated = $request->validate([
            'auto_assign_courier' => 'nullable|boolean',
            'check_courier_shift' => 'nullable|boolean',
            'max_delivery_time' => 'required|integer|min:10|max:180',
        ]);

        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        if (!$branch) {
            return back()->with('error', 'Sube bulunamadi.');
        }

        $settings = \App\Models\BranchSetting::getOrCreateForBranch($branch->id);
        $settings->update([
            'auto_assign_courier' => $request->boolean('auto_assign_courier'),
            'check_courier_shift' => $request->boolean('check_courier_shift'),
            'max_delivery_time' => $validated['max_delivery_time'],
        ]);

        return back()->with('success', 'Kurye ayarlari basariyla guncellendi.');
    }

    public function ayarlarUygulama()
    {
        $settings = \App\Models\ApplicationSetting::getOrCreateForUser(auth()->id());
        return view('bayi.ayarlar.uygulama', compact('settings'));
    }

    public function updateUygulama(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|in:tr,en',
            'timezone' => 'required|string',
            'sound_notifications' => 'nullable|boolean',
        ]);

        $settings = \App\Models\ApplicationSetting::getOrCreateForUser(auth()->id());
        $settings->update([
            'language' => $validated['language'],
            'timezone' => $validated['timezone'],
            'sound_notifications' => $request->boolean('sound_notifications'),
        ]);

        return back()->with('success', 'Uygulama ayarlari basariyla guncellendi.');
    }

    public function ayarlarHavuz()
    {
        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        $settings = $branch ? \App\Models\BranchSetting::getOrCreateForBranch($branch->id) : null;

        return view('bayi.ayarlar.havuz', compact('settings'));
    }

    public function updateHavuz(Request $request)
    {
        $validated = $request->validate([
            'pool_enabled' => 'nullable|boolean',
            'pool_wait_time' => 'required|integer|min:1|max:60',
            'pool_auto_assign' => 'nullable|boolean',
            'pool_max_orders' => 'required|integer|min:1|max:100',
            'pool_priority_by_distance' => 'nullable|boolean',
            'pool_notify_couriers' => 'nullable|boolean',
        ]);

        $branch = \App\Models\Branch::whereNull('parent_id')->first();

        if (!$branch) {
            return back()->with('error', 'Şube bulunamadı.');
        }

        $settings = \App\Models\BranchSetting::getOrCreateForBranch($branch->id);

        $settings->update([
            'pool_enabled' => $request->boolean('pool_enabled'),
            'pool_wait_time' => $validated['pool_wait_time'],
            'pool_auto_assign' => $request->boolean('pool_auto_assign'),
            'pool_max_orders' => $validated['pool_max_orders'],
            'pool_priority_by_distance' => $request->boolean('pool_priority_by_distance'),
            'pool_notify_couriers' => $request->boolean('pool_notify_couriers'),
        ]);

        return back()->with('success', 'Havuz ayarları başarıyla güncellendi.');
    }

    public function ayarlarBildirim()
    {
        $settings = \App\Models\NotificationSetting::getOrCreateForUser(auth()->id());
        return view('bayi.ayarlar.bildirim', compact('settings'));
    }

    public function updateBildirim(Request $request)
    {
        $settings = \App\Models\NotificationSetting::getOrCreateForUser(auth()->id());
        $settings->update([
            'new_order_notification' => $request->boolean('new_order_notification'),
            'order_status_notification' => $request->boolean('order_status_notification'),
            'email_new_order' => $request->boolean('email_new_order'),
            'sms_enabled' => $request->boolean('sms_enabled'),
        ]);

        return back()->with('success', 'Bildirim ayarlari basariyla guncellendi.');
    }

    public function tema()
    {
        $themeSettings = \App\Models\ThemeSetting::getOrCreateForUser(auth()->id());
        return view('bayi.tema', compact('themeSettings'));
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_mode' => 'required|in:light,dark,system',
            'accent_color' => 'nullable|string|max:20',
            'compact_mode' => 'nullable|boolean',
            'animations_enabled' => 'nullable|boolean',
            'sidebar_auto_hide' => 'nullable|boolean',
            'sidebar_width' => 'nullable|in:narrow,normal,wide',
        ]);

        $themeSettings = \App\Models\ThemeSetting::getOrCreateForUser(auth()->id());
        $themeSettings->update([
            'theme_mode' => $validated['theme_mode'],
            'accent_color' => $validated['accent_color'] ?? '#000000',
            'compact_mode' => $request->boolean('compact_mode'),
            'animations_enabled' => $request->boolean('animations_enabled'),
            'sidebar_auto_hide' => $request->boolean('sidebar_auto_hide'),
            'sidebar_width' => $validated['sidebar_width'] ?? 'normal',
        ]);

        return redirect()->route('bayi.tema')->with('success', 'Tema ayarları başarıyla güncellendi.');
    }

    public function yardim()
    {
        return view('bayi.yardim');
    }

    // Kurye Şifre Yönetimi
    public function kuryeSifreAyarla(Request $request, Courier $courier)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $courier->update([
            'password' => $request->password, // Will be hashed via model cast
            'is_app_enabled' => true,
        ]);

        return back()->with('success', "{$courier->name} için şifre başarıyla ayarlandı.");
    }

    public function kuryeAppToggle(Request $request, Courier $courier)
    {
        $courier->update([
            'is_app_enabled' => !$courier->is_app_enabled,
        ]);

        $status = $courier->is_app_enabled ? 'aktif edildi' : 'devre dışı bırakıldı';

        return response()->json([
            'success' => true,
            'message' => "{$courier->name} için uygulama erişimi {$status}.",
            'is_app_enabled' => $courier->is_app_enabled,
        ]);
    }

    // Branch Settings Methods
    public function updateBranchSettings(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'nickname' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'courier_enabled' => 'nullable|boolean',
            'balance_tracking' => 'nullable|boolean',
            'cash_balance_tracking' => 'nullable|boolean',
            'map_display' => 'nullable|boolean',
        ]);

        // Update branch basic info
        $branch->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        // Update or create settings
        $branch->settings()->updateOrCreate(
            ['branch_id' => $branch->id],
            [
                'nickname' => $validated['nickname'],
                'courier_enabled' => $request->boolean('courier_enabled'),
                'balance_tracking' => $request->boolean('balance_tracking'),
                'cash_balance_tracking' => $request->boolean('cash_balance_tracking'),
                'map_display' => $request->boolean('map_display'),
            ]
        );

        return back()->with('success', 'Ayarlar başarıyla güncellendi.');
    }

    public function addBranchBalance(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if (!$branch->settings) {
            return back()->with('error', 'İşletme ayarları bulunamadı.');
        }

        // Update balance
        $newBalance = $branch->settings->current_balance + $validated['amount'];
        $branch->settings->update([
            'current_balance' => $newBalance,
        ]);

        // Create transaction record
        \App\Models\Transaction::create([
            'user_id' => auth()->id(),
            'branch_id' => $branch->id,
            'type' => \App\Models\Transaction::TYPE_BALANCE_ADDITION,
            'amount' => $validated['amount'],
            'currency' => 'TRY',
            'status' => \App\Models\Transaction::STATUS_COMPLETED,
            'description' => "İşletme bakiyesi eklendi",
            'paid_at' => now(),
        ]);

        return back()->with('success', '₺' . number_format($validated['amount'], 2) . ' bakiye başarıyla eklendi.');
    }

    public function getBranchOrders(Request $request, \App\Models\Branch $branch)
    {
        $type = $request->get('type', 'past'); // past or cancelled
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = $branch->orders()->with('courier');

        if ($type === 'past') {
            $query->whereIn('status', ['delivered']);
        } else {
            $query->where('status', 'cancelled');
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $orders = $query->orderBy('created_at', 'desc')->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => number_format($order->total, 2),
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'courier_name' => $order->courier?->name ?? '-',
                'created_at' => $order->created_at->format('d.m.Y H:i'),
                'delivered_at' => $order->delivered_at?->format('d.m.Y H:i'),
                'cancelled_at' => $order->cancelled_at?->format('d.m.Y H:i'),
            ];
        });

        return response()->json($orders);
    }

    public function getBranchStatistics(Request $request, \App\Models\Branch $branch)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            $startDate = \Carbon\Carbon::parse($startDate)->startOfDay();
            $endDate = \Carbon\Carbon::parse($endDate)->endOfDay();
        }

        $orders = $branch->orders()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $deliveredOrders = $orders->where('status', 'delivered');
        $cancelledOrders = $orders->where('status', 'cancelled');

        // Payment method breakdown
        $paymentMethods = $deliveredOrders->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method,
                'method_label' => match($method) {
                    'cash' => 'Nakit',
                    'card' => 'Kart',
                    'online' => 'Online',
                    default => $method,
                },
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        })->values();

        return response()->json([
            'total_orders' => $orders->count(),
            'cancelled_orders' => $cancelledOrders->count(),
            'total_amount' => $deliveredOrders->sum('total'),
            'payment_methods' => $paymentMethods,
            'start_date' => $startDate->format('d.m.Y H:i'),
            'end_date' => $endDate->format('d.m.Y H:i'),
        ]);
    }

    public function getBranchDetailedStatistics(Request $request, \App\Models\Branch $branch)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } else {
            $startDate = \Carbon\Carbon::parse($startDate)->startOfDay();
            $endDate = \Carbon\Carbon::parse($endDate)->endOfDay();
        }

        $orders = $branch->orders()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $deliveredOrders = $orders->where('status', 'delivered');
        $cancelledOrders = $orders->where('status', 'cancelled');

        // Paid cancellations (assuming cancelled orders with status = cancelled and is_paid = true are paid cancellations)
        $paidCancellations = $cancelledOrders->where('is_paid', true);

        // Payment method breakdown
        $paymentMethods = $deliveredOrders->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method,
                'method_label' => match($method) {
                    'cash' => 'Nakit',
                    'card' => 'Kart',
                    'online' => 'Online',
                    default => $method,
                },
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        })->values();

        return response()->json([
            'overall' => [
                'total_orders' => $orders->count(),
                'cancelled_orders' => $cancelledOrders->count(),
                'paid_cancellations' => $paidCancellations->count(),
                'total_amount' => $deliveredOrders->sum('total'),
                'payment_methods' => $paymentMethods,
            ],
            'dealer' => [
                'total_orders' => $orders->count(),
                'cancelled_orders' => $cancelledOrders->count(),
                'paid_cancellations' => $paidCancellations->count(),
                'total_amount' => $deliveredOrders->sum('total'),
                'payment_methods' => $paymentMethods,
            ],
            'business' => [
                'total_orders' => $orders->count(),
                'cancelled_orders' => $cancelledOrders->count(),
                'paid_cancellations' => $paidCancellations->count(),
                'total_amount' => $deliveredOrders->sum('total'),
                'payment_methods' => $paymentMethods,
            ],
        ]);
    }

    // Pricing Policy Methods
    public function storePricingPolicy(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'type' => 'required|in:business,courier',
            'policy_type' => 'required|in:fixed,package_based,distance_based,periodic,unit_price,consecutive_discount',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $policy = $branch->pricingPolicies()->create([
            'type' => $validated['type'],
            'policy_type' => $validated['policy_type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fiyatlandırma politikası başarıyla oluşturuldu.',
            'policy' => $policy,
        ]);
    }

    public function updatePricingPolicy(Request $request, \App\Models\Branch $branch, \App\Models\PricingPolicy $policy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $policy->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fiyatlandırma politikası güncellendi.',
            'policy' => $policy->fresh(),
        ]);
    }

    public function deletePricingPolicy(\App\Models\Branch $branch, \App\Models\PricingPolicy $policy)
    {
        $policy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fiyatlandırma politikası silindi.',
        ]);
    }

    public function storePricingPolicyRule(Request $request, \App\Models\PricingPolicy $policy)
    {
        $validated = $request->validate([
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        $rule = $policy->rules()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kural başarıyla eklendi.',
            'rule' => $rule,
        ]);
    }

    public function updatePricingPolicyRule(Request $request, \App\Models\PricingPolicyRule $rule)
    {
        $validated = $request->validate([
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kural güncellendi.',
            'rule' => $rule->fresh(),
        ]);
    }

    public function deletePricingPolicyRule(\App\Models\PricingPolicyRule $rule)
    {
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kural silindi.',
        ]);
    }

    // ============================================
    // NAKİT ÖDEMELER
    // ============================================

    public function nakitOdemeler(Request $request)
    {
        // Tüm kuryeler (form için)
        $allCouriers = Courier::orderBy('name')->get();

        // Son bakiyeler için sadece işlem yapılmış kuryeler
        $couriersWithTransactions = Courier::whereHas('cashTransactions')
            ->with(['cashTransactions' => function($q) {
                $q->latest()->take(5);
            }])
            ->withCount(['cashTransactions as total_cash_received' => function($q) {
                $q->where('type', \App\Models\CashTransaction::TYPE_PAYMENT_RECEIVED)
                  ->where('status', \App\Models\CashTransaction::STATUS_COMPLETED);
            }])
            ->orderBy('name')
            ->get();

        // Toplam nakit (tüm kuryelerin bakiyelerinin toplamı)
        $totalCash = Courier::sum('cash_balance');

        // Son işlemler
        $recentTransactions = \App\Models\CashTransaction::with(['courier', 'branch', 'creator'])
            ->latest()
            ->take(20)
            ->get();

        return view('bayi.nakit-odemeler', compact('allCouriers', 'couriersWithTransactions', 'totalCash', 'recentTransactions'));
    }

    public function nakitOdemeStore(Request $request)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:payment_received,advance_given',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = \App\Models\CashTransaction::STATUS_COMPLETED;

        // Create transaction
        $transaction = \App\Models\CashTransaction::create($validated);

        // Apply to courier balance
        $transaction->applyToBalance();

        return response()->json([
            'success' => true,
            'message' => 'İşlem başarıyla kaydedildi.',
            'transaction' => $transaction->load(['courier', 'branch', 'creator']),
        ]);
    }

    public function nakitOdemeCancel(\App\Models\CashTransaction $transaction)
    {
        if ($transaction->status === \App\Models\CashTransaction::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem zaten iptal edilmiş.',
            ], 400);
        }

        // Reverse from balance
        if ($transaction->status === \App\Models\CashTransaction::STATUS_COMPLETED) {
            $transaction->reverseFromBalance();
        }

        // Mark as cancelled
        $transaction->update(['status' => \App\Models\CashTransaction::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'İşlem iptal edildi.',
        ]);
    }

    public function nakitOdemeHistory(Courier $courier)
    {
        $transactions = $courier->cashTransactions()
            ->with(['branch', 'creator'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'transactions' => $transactions,
            'courier' => $courier,
        ]);
    }

    // ============================================
    // KURYE DETAY ROUTES
    // ============================================

    public function kuryeDetay(Courier $courier)
    {
        // Eager load relationships
        $courier->load([
            'pricingPolicy.rules',
            'zones',
            'orders' => function($q) {
                $q->whereIn('status', ['delivered', 'cancelled'])
                  ->latest()
                  ->take(10);
            }
        ]);

        // Hızlı istatistikler hesapla
        $totalOrders = $courier->orders()->whereIn('status', ['delivered', 'cancelled'])->count();
        $deliveredOrders = $courier->orders()->where('status', 'delivered')->count();
        $cancelledOrders = $courier->orders()->where('status', 'cancelled')->count();
        $successRate = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0;

        // Atanabilir politikaları getir
        $availablePolicies = \App\Models\PricingPolicy::where('type', 'courier')
            ->where('is_active', true)
            ->with('rules')
            ->get();

        return view('bayi.kurye-detay', compact(
            'courier',
            'totalOrders',
            'deliveredOrders',
            'cancelledOrders',
            'successRate',
            'availablePolicies'
        ));
    }

    public function kuryePastOrders(Request $request, Courier $courier)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $orders = $courier->orders()
            ->with('branch')
            ->whereIn('status', ['delivered', 'cancelled'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'total' => number_format($order->total, 2),
                    'payment_method' => $order->payment_method === 'cash' ? 'Nakit' : 'Kart',
                    'status' => $order->status,
                    'status_label' => $order->status === 'delivered' ? 'Teslim Edildi' : 'İptal Edildi',
                    'branch_name' => $order->branch->name ?? '-',
                    'created_at' => $order->created_at->format('d.m.Y H:i'),
                    'delivered_at' => $order->delivered_at ? $order->delivered_at->format('d.m.Y H:i') : '-',
                ];
            });

        return response()->json($orders);
    }

    public function kuryeStatistics(Request $request, Courier $courier)
    {
        $period = $request->input('period', 'week'); // day, week, month

        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $orders = $courier->orders()
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->get();

        $totalOrders = $orders->count();
        $delivered = $orders->where('status', 'delivered')->count();
        $cancelled = $orders->where('status', 'cancelled')->count();
        $successRate = $totalOrders > 0 ? round(($delivered / $totalOrders) * 100, 1) : 0;
        $totalEarnings = $orders->where('status', 'delivered')->sum('total');

        // Saatlik dağılım
        $hourlyDistribution = array_fill(0, 24, 0);
        foreach ($orders as $order) {
            $hour = $order->created_at->hour;
            $hourlyDistribution[$hour]++;
        }

        // Günlük istatistikler
        $dailyStats = $orders->groupBy(function($order) {
            return $order->created_at->format('d.m');
        })->map(function($dayOrders, $date) {
            return [
                'date' => $date,
                'day' => \Carbon\Carbon::createFromFormat('d.m', $date)->locale('tr')->dayName,
                'orders' => $dayOrders->count(),
                'delivered' => $dayOrders->where('status', 'delivered')->count(),
                'revenue' => number_format($dayOrders->where('status', 'delivered')->sum('total'), 2),
            ];
        })->values();

        // Bölge istatistikleri
        $zoneStats = $courier->zones->map(function($zone) use ($courier, $startDate) {
            $zoneOrders = $courier->orders()
                ->where('created_at', '>=', $startDate)
                ->whereHas('branch', function($q) use ($zone) {
                    $q->whereHas('zones', function($q2) use ($zone) {
                        $q2->where('zones.id', $zone->id);
                    });
                })
                ->count();

            return [
                'zone_name' => $zone->name,
                'orders' => $zoneOrders,
            ];
        });

        return response()->json([
            'summary' => [
                'total_orders' => $totalOrders,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'success_rate' => $successRate,
                'total_earnings' => number_format($totalEarnings, 2),
            ],
            'hourly_distribution' => $hourlyDistribution,
            'daily_stats' => $dailyStats,
            'zone_stats' => $zoneStats,
        ]);
    }

    public function kuryeMesaiLogs(Request $request, Courier $courier)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $timeLogs = $courier->timeLogs()
            ->forDateRange($startDate . ' 00:00:00', $endDate . ' 23:59:59')
            ->orderBy('event_time', 'asc')
            ->get();

        // Günlere göre grupla
        $logsByDate = $timeLogs->groupBy(function($log) {
            return $log->event_time->format('Y-m-d');
        });

        $dailyStats = [];
        $totalWorkHours = 0;
        $totalBreakHours = 0;
        $daysWorked = 0;

        foreach ($logsByDate as $date => $logs) {
            $clockIn = $logs->firstWhere('event_type', \App\Models\CourierTimeLog::CLOCK_IN);
            $clockOut = $logs->firstWhere('event_type', \App\Models\CourierTimeLog::CLOCK_OUT);

            $breakStart = $logs->where('event_type', \App\Models\CourierTimeLog::BREAK_START);
            $breakEnd = $logs->where('event_type', \App\Models\CourierTimeLog::BREAK_END);

            $workHours = 0;
            $breakHours = 0;

            if ($clockIn && $clockOut) {
                $workHours = $clockOut->event_time->diffInMinutes($clockIn->event_time) / 60;
                $daysWorked++;
            }

            // Mola sürelerini hesapla
            foreach ($breakStart as $index => $start) {
                $end = $breakEnd->get($index);
                if ($end) {
                    $breakHours += $end->event_time->diffInMinutes($start->event_time) / 60;
                }
            }

            $netWorkHours = $workHours - $breakHours;
            $totalWorkHours += $workHours;
            $totalBreakHours += $breakHours;

            $dailyStats[] = [
                'date' => \Carbon\Carbon::parse($date)->format('d.m.Y'),
                'day' => \Carbon\Carbon::parse($date)->locale('tr')->dayName,
                'clock_in' => $clockIn ? $clockIn->event_time->format('H:i') : '-',
                'clock_out' => $clockOut ? $clockOut->event_time->format('H:i') : '-',
                'total_work_hours' => number_format($workHours, 1),
                'break_hours' => number_format($breakHours, 1),
                'net_work_hours' => number_format($netWorkHours, 1),
            ];
        }

        $avgDailyHours = $daysWorked > 0 ? $totalWorkHours / $daysWorked : 0;

        return response()->json([
            'daily_stats' => $dailyStats,
            'summary' => [
                'total_work_hours' => number_format($totalWorkHours, 1),
                'total_break_hours' => number_format($totalBreakHours, 1),
                'avg_daily_hours' => number_format($avgDailyHours, 1),
                'days_worked' => $daysWorked,
            ],
            'time_logs' => $timeLogs->map(function($log) {
                return [
                    'event_type' => $log->getEventTypeLabel(),
                    'event_time' => $log->event_time->format('d.m.Y H:i'),
                    'color' => $log->getEventTypeColor(),
                ];
            }),
        ]);
    }

    public function kuryePricingPolicyOlustur(Request $request)
    {
        $validated = $request->validate([
            'policy_type' => 'required|in:fixed,package_based,distance_based,periodic,unit_price,consecutive_discount',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'fixed_price' => 'nullable|numeric|min:0',
            'fixed_percentage' => 'nullable|numeric|min:0|max:100',
            'price_per_km' => 'nullable|numeric|min:0',
            'rules' => 'nullable|array',
            'rules.*.min_value' => 'nullable|numeric|min:0',
            'rules.*.max_value' => 'nullable|numeric|min:0',
            'rules.*.price' => 'nullable|numeric|min:0',
            'rules.*.percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Ana branch'i kullan (kurye politikaları için)
        $branch = \App\Models\Branch::whereNull('parent_id')->first();

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'İşletme bulunamadı.',
            ], 404);
        }

        $policy = $branch->pricingPolicies()->create([
            'type' => 'courier',
            'policy_type' => $validated['policy_type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->boolean('is_active'),
        ]);

        // Politika tipine göre kuralları oluştur
        $policyType = $validated['policy_type'];

        if ($policyType === 'fixed') {
            // Sabit fiyat/yüzde için tek kural
            if ($request->fixed_price || $request->fixed_percentage) {
                $policy->rules()->create([
                    'min_value' => 0,
                    'max_value' => 999999,
                    'price' => $request->fixed_price ?? 0,
                    'percentage' => $request->fixed_percentage ?? 0,
                    'order' => 1,
                ]);
            }
        } elseif ($policyType === 'unit_price') {
            // Birim fiyat için tek kural
            if ($request->price_per_km) {
                $policy->rules()->create([
                    'min_value' => 0,
                    'max_value' => 999999,
                    'price' => $request->price_per_km,
                    'percentage' => 0,
                    'order' => 1,
                ]);
            }
        } else {
            // Diğer tipler için çoklu kurallar
            $rules = $request->input('rules', []);
            foreach ($rules as $index => $rule) {
                if (($rule['price'] ?? 0) > 0 || ($rule['percentage'] ?? 0) > 0) {
                    $policy->rules()->create([
                        'min_value' => $rule['min_value'] ?? 0,
                        'max_value' => $rule['max_value'] ?? 999999,
                        'price' => $rule['price'] ?? 0,
                        'percentage' => $rule['percentage'] ?? 0,
                        'order' => $index + 1,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Fiyatlandırma politikası başarıyla oluşturuldu.',
            'policy' => $policy->load('rules'),
        ]);
    }

    public function kuryePricingPolicyAta(Request $request, Courier $courier)
    {
        $request->validate([
            'pricing_policy_id' => 'nullable|exists:pricing_policies,id',
        ]);

        $courier->update([
            'pricing_policy_id' => $request->pricing_policy_id,
        ]);

        $message = $request->pricing_policy_id
            ? "Fiyatlandırma politikası başarıyla atandı."
            : "Fiyatlandırma politikası kaldırıldı.";

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function pricingPolicySil(\App\Models\PricingPolicy $pricingPolicy)
    {
        // Önce bu politikayı kullanan kuryeler varsa, ilişkiyi kaldır
        \App\Models\Courier::where('pricing_policy_id', $pricingPolicy->id)
            ->update(['pricing_policy_id' => null]);

        // Politikanın kurallarını sil
        $pricingPolicy->rules()->delete();

        // Politikayı sil
        $pricingPolicy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fiyatlandırma politikası başarıyla silindi.',
        ]);
    }

    public function kuryeAyarlarGuncelle(Request $request, Courier $courier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $courier->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'Kurye ayarları başarıyla güncellendi.');
    }

    public function kuryeSil(Courier $courier)
    {
        // Aktif sipariş kontrolü
        $activeOrdersCount = $courier->orders()->active()->count();

        if ($activeOrdersCount > 0) {
            return back()->with('error', 'Aktif siparişi olan kurye silinemez.');
        }

        $courierName = $courier->name;
        $courier->delete();

        return redirect()->route('bayi.kuryelerim')
            ->with('success', "{$courierName} başarıyla silindi.");
    }
}

