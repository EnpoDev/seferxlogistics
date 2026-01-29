<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;

class BayiCourierController extends Controller
{
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

    public function kuryeSil(Courier $courier)
    {
        // Aktif sipariş kontrolü
        $activeOrdersCount = $courier->orders()->active()->count();

        if ($activeOrdersCount > 0) {
            return back()->with('error', __('messages.error.courier_has_active_orders_delete'));
        }

        $courierName = $courier->name;
        $courier->delete();

        return redirect()->route('bayi.kuryelerim')
            ->with('success', "{$courierName} başarıyla silindi.");
    }

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

        return back()->with('success', __('messages.success.courier_settings_updated'));
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
        $validated['branch_id'] = auth()->user()->branch_id;

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
}
