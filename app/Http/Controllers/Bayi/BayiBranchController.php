<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BayiBranchController extends Controller
{
    /**
     * Branch sahiplik kontrolu
     */
    private function checkBranchOwnership(\App\Models\Branch $branch): void
    {
        // Bayi'nin kendi olusturdugu branch mi kontrol et
        // Branch user_id veya parent branch'in user_id'si kontrol edilir
        $userId = auth()->id();

        // Direkt sahiplik
        if ($branch->user_id === $userId) {
            return;
        }

        // Bayi'nin olusturdugu isletmenin user'ina ait mi (isletme hesabi)
        $branchUser = \App\Models\User::find($branch->user_id);
        if ($branchUser && $branchUser->parent_id === $userId) {
            return;
        }

        abort(403, 'Bu isletmeye erisim yetkiniz yok.');
    }

    public function isletmelerim(Request $request)
    {
        $userId = auth()->id();

        // SADECE KENDI ISLETMELERINI GOSTER
        // Bayi'nin kendi olusturdugu veya bayi'ye bagli kullanicilarin isletmeleri
        $userIds = \App\Models\User::where('id', $userId)
            ->orWhere('parent_id', $userId)
            ->pluck('id');

        $query = \App\Models\Branch::whereIn('user_id', $userIds)
            ->whereNull('parent_id')
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
            if ($parent) {
                $this->checkBranchOwnership($parent);
            }
        }
        return view('bayi.isletme-ekle', compact('parent'));
    }

    public function isletmeKaydet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'required|in:active,passive',
            'parent_id' => 'nullable|exists:branches,id',
            'is_main' => 'sometimes|boolean'
        ]);

        // Isletme icin kullanici hesabi olustur
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'role' => 'isletme',
            'parent_id' => auth()->id(),
        ]);

        $validated['is_active'] = $request->status === 'active';
        $validated['is_main'] = $request->has('is_main') && !$request->parent_id;
        $validated['user_id'] = $user->id; // Isletmenin kendi user_id'si

        if ($validated['is_main']) {
            \App\Models\Branch::where('user_id', auth()->id())->where('is_main', true)->update(['is_main' => false]);
        }

        // Password'u branch'e kaydetme
        unset($validated['password']);

        $branch = \App\Models\Branch::create($validated);

        if ($branch->parent_id) {
            return redirect()->route('bayi.isletme-detay', $branch->parent_id)->with('success', __('messages.success.branch_created'));
        }

        return redirect()->route('bayi.isletmelerim')->with('success', __('messages.success.restaurant_created'));
    }

    public function isletmeDetay(\App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

        // Siparis istatistikleri
        $stats = [
            'today_orders' => $branch->orders()->whereDate('created_at', today())->count(),
            'today_revenue' => $branch->orders()->whereDate('created_at', today())->where('status', 'delivered')->sum('total'),
            'week_orders' => $branch->orders()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'week_revenue' => $branch->orders()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'delivered')->sum('total'),
            'month_orders' => $branch->orders()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'month_revenue' => $branch->orders()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'delivered')->sum('total'),
            'total_orders' => $branch->orders()->count(),
            'total_revenue' => $branch->orders()->where('status', 'delivered')->sum('total'),
            'pending_orders' => $branch->orders()->where('status', 'pending')->count(),
            'on_delivery_orders' => $branch->orders()->where('status', 'on_delivery')->count(),
            'delivered_orders' => $branch->orders()->where('status', 'delivered')->count(),
            'cancelled_orders' => $branch->orders()->where('status', 'cancelled')->count(),
        ];

        // Tamamlanma orani
        $totalCompleted = $stats['delivered_orders'] + $stats['cancelled_orders'];
        $stats['completion_rate'] = $totalCompleted > 0
            ? round(($stats['delivered_orders'] / $totalCompleted) * 100, 1)
            : 0;

        // Ortalama teslimat suresi (son 30 gun)
        $deliveredOrders = $branch->orders()
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->get();

        $avgDeliveryTime = 0;
        if ($deliveredOrders->count() > 0) {
            $totalMinutes = $deliveredOrders->sum(function ($order) {
                return $order->created_at->diffInMinutes($order->delivered_at);
            });
            $avgDeliveryTime = round($totalMinutes / $deliveredOrders->count());
        }
        $stats['avg_delivery_time'] = $avgDeliveryTime;

        // Son siparisler
        $recentOrders = $branch->orders()
            ->with('courier')
            ->latest()
            ->take(10)
            ->get();

        return view('bayi.isletme-detay', compact('branch', 'stats', 'recentOrders'));
    }

    public function isletmeDuzenle(\App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

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
        $this->checkBranchOwnership($branch);

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
            return redirect()->route('bayi.isletme-detay', $branch->parent_id)->with('success', __('messages.success.branch_updated'));
        }

        return redirect()->route('bayi.isletmelerim')->with('success', __('messages.success.restaurant_updated'));
    }

    public function isletmeSil(Request $request, \App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

        // Isim onay kontrolu
        $request->validate([
            'confirm_name' => 'required|string',
        ]);

        if ($request->confirm_name !== $branch->name) {
            return back()->with('error', 'Isletme adi eslesmedi. Silme islemi iptal edildi.');
        }

        $branchName = $branch->name;
        $branch->delete();

        return redirect()->route('bayi.isletmelerim')->with('success', "{$branchName} basariyla silindi.");
    }

    /**
     * Isletme olarak giris yap (impersonation)
     */
    public function isletmeOlarakGiris(\App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

        // Isletmenin kullanici hesabini bul
        $isletmeUser = \App\Models\User::find($branch->user_id);

        if (!$isletmeUser) {
            return back()->with('error', 'Isletme kullanici hesabi bulunamadi.');
        }

        // Mevcut paneli kaydet
        $previousPanel = session('active_panel', 'bayi');

        // Impersonation bilgilerini kaydet
        $impersonationData = [
            'impersonating_from' => auth()->id(),
            'impersonating_from_name' => auth()->user()->name,
            'impersonated_branch_id' => $branch->id,
            'impersonated_branch_name' => $branch->name,
            'impersonating_from_panel' => $previousPanel,
        ];

        // Isletme kullanicisi olarak giris yap (session regenerate etmeden)
        auth()->login($isletmeUser, false);

        // Session regenerate olduktan sonra impersonation bilgilerini tekrar kaydet
        session($impersonationData);

        // Paneli isletme olarak degistir
        session(['active_panel' => 'isletme']);
        session()->save();

        return redirect()->route('dashboard')->with('success', "{$branch->name} olarak giris yapildi.");
    }

    /**
     * Bayi paneline geri don (stop impersonation)
     */
    public function bayiPanelineGeriDon()
    {
        $originalUserId = session('impersonating_from');
        $originalPanel = session('impersonating_from_panel', 'bayi');

        if (!$originalUserId) {
            return redirect()->route('dashboard');
        }

        $originalUser = \App\Models\User::find($originalUserId);

        if (!$originalUser) {
            session()->forget(['impersonating_from', 'impersonating_from_name', 'impersonated_branch_id', 'impersonated_branch_name', 'impersonating_from_panel']);
            return redirect()->route('login');
        }

        // Orijinal kullanici olarak giris yap
        auth()->login($originalUser, false);

        // Session'i temizle ve paneli geri yukle
        session()->forget(['impersonating_from', 'impersonating_from_name', 'impersonated_branch_id', 'impersonated_branch_name', 'impersonating_from_panel']);
        session(['active_panel' => $originalPanel]);
        session()->save();

        return redirect()->route('bayi.isletmelerim')->with('success', 'Bayi paneline geri donuldu.');
    }

    public function updateBranchSettings(Request $request, \App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

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

        return back()->with('success', __('messages.success.settings_saved'));
    }

    public function addBranchBalance(Request $request, \App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if (!$branch->settings) {
            return back()->with('error', __('messages.error.branch_settings_not_found'));
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
        $this->checkBranchOwnership($branch);

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
        $this->checkBranchOwnership($branch);

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
        $this->checkBranchOwnership($branch);

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

        // Paid cancellations
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

    public function storePricingPolicy(Request $request, \App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

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
        $this->checkBranchOwnership($branch);

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
        $this->checkBranchOwnership($branch);

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

    public function isletmeOdemeleri()
    {
        $userId = auth()->id();

        // SADECE KENDI ISLETMELERINI GOSTER
        $userIds = \App\Models\User::where('id', $userId)
            ->orWhere('parent_id', $userId)
            ->pluck('id');

        $branches = \App\Models\Branch::whereIn('user_id', $userIds)
            ->with(['orders' => function($q) {
                $q->where('status', 'delivered')
                  ->whereMonth('created_at', now()->month);
            }])->get();

        return view('bayi.odemeler.isletme', compact('branches'));
    }

    public function isletmeOdemeStore(Request $request, \App\Models\Branch $branch)
    {
        $this->checkBranchOwnership($branch);

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
        $userId = auth()->id();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // SADECE KENDI ISLETMELERININ RAPORU
        $userIds = \App\Models\User::where('id', $userId)
            ->orWhere('parent_id', $userId)
            ->pluck('id');

        $branches = \App\Models\Branch::whereIn('user_id', $userIds)
            ->with(['orders' => function($q) use ($startDate, $endDate) {
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
}
