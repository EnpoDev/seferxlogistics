<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Courier;
use App\Models\Restaurant;
use App\Models\Product;
use App\Services\CourierAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private CourierAssignmentService $courierAssignmentService
    ) {}

    public function index()
    {
        // Today's statistics
        $todayStats = [
            'orders' => Order::today()->count(),
            'revenue' => Order::today()->where('status', 'delivered')->sum('total'),
            'pending' => Order::today()->where('status', 'pending')->count(),
            'delivered' => Order::today()->where('status', 'delivered')->count(),
            'cancelled' => Order::today()->where('status', 'cancelled')->count(),
            'new_customers' => Customer::whereDate('created_at', today())->count(),
        ];

        // Overall statistics
        $overallStats = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total'),
            'total_customers' => Customer::count(),
            'total_restaurants' => Restaurant::count(),
            'total_products' => Product::count(),
            'avg_order_value' => Order::where('status', 'delivered')->avg('total') ?? 0,
        ];

        // Courier statistics
        $courierStats = $this->courierAssignmentService->getCourierWorkloadStats();

        // Active orders
        $activeOrders = Order::with(['customer', 'courier', 'restaurant', 'items'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['customer', 'courier', 'restaurant'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Orders by status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Revenue by day (last 7 days)
        $revenueByDay = Order::where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => $item->revenue,
                    'orders' => $item->orders,
                ];
            });

        // Top products
        $topProducts = DB::table('order_items')
            ->select('product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // Top customers
        $topCustomers = Customer::orderByDesc('total_spent')
            ->take(5)
            ->get();

        // Available couriers
        $availableCouriers = Courier::where('status', 'available')
            ->get()
            ->filter(fn($c) => $c->isOnShift());

        return view('pages.dashboard', compact(
            'todayStats',
            'overallStats',
            'courierStats',
            'activeOrders',
            'recentOrders',
            'ordersByStatus',
            'revenueByDay',
            'topProducts',
            'topCustomers',
            'availableCouriers'
        ));
    }
}

