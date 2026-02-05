<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;

class BayiStatsController extends Controller
{
    public function kullaniciYonetimi()
    {
        $users = \App\Models\User::where('parent_id', auth()->id())
            ->orWhere('id', auth()->id())
            ->orderBy('name')
            ->paginate(20);
        return view('bayi.kullanici-yonetimi', compact('users'));
    }

    public function kullaniciEkle()
    {
        return view('bayi.kullanici-ekle');
    }

    public function kullaniciKaydet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => 'staff',
            'parent_id' => auth()->id(),
        ]);

        return redirect()->route('bayi.kullanici-yonetimi')
            ->with('success', "{$user->name} başarıyla eklendi.");
    }

    public function kullaniciDuzenle(\App\Models\User $user)
    {
        // Only allow editing own staff
        if ($user->parent_id !== auth()->id() && $user->id !== auth()->id()) {
            abort(403, 'Bu kullanıcıyı düzenleme yetkiniz yok.');
        }
        return view('bayi.kullanici-duzenle', compact('user'));
    }

    public function kullaniciGuncelle(Request $request, \App\Models\User $user)
    {
        // Only allow editing own staff
        if ($user->parent_id !== auth()->id() && $user->id !== auth()->id()) {
            abort(403, 'Bu kullanıcıyı düzenleme yetkiniz yok.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('bayi.kullanici-yonetimi')
            ->with('success', "{$user->name} başarıyla güncellendi.");
    }

    public function kullaniciSil(\App\Models\User $user)
    {
        // Only allow deleting own staff
        if ($user->parent_id !== auth()->id()) {
            abort(403, 'Bu kullanıcıyı silme yetkiniz yok.');
        }

        // Cannot delete yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('bayi.kullanici-yonetimi')
            ->with('success', "{$userName} başarıyla silindi.");
    }

    public function istatistik()
    {
        $userId = auth()->id();

        // Calculate real average delivery time - SADECE KENDI SIPARISLERI
        $deliveredOrders = Order::where('user_id', $userId)
            ->whereNotNull('delivered_at')
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

        // Calculate completion rate - SADECE KENDI SIPARISLERI
        $totalOrders = Order::where('user_id', $userId)
            ->whereDate('created_at', '>=', now()->subDays(30))->count();
        $completedOrders = Order::where('user_id', $userId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->where('status', 'delivered')
            ->count();
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        $stats = [
            'today_orders' => Order::where('user_id', $userId)->whereDate('created_at', today())->count(),
            'today_revenue' => Order::where('user_id', $userId)->whereDate('created_at', today())
                ->where('status', 'delivered')->sum('total'),
            'active_couriers' => Courier::where('user_id', $userId)->whereIn('status', ['available', 'busy'])->count(),
            'avg_delivery_time' => $avgDeliveryTime ?: 0,
            'completion_rate' => $completionRate,
            'pending_orders' => Order::where('user_id', $userId)->where('status', 'pending')->count(),
            'on_delivery_orders' => Order::where('user_id', $userId)->where('status', 'on_delivery')->count(),
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
}
