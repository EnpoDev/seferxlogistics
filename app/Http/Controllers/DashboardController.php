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
        $user = auth()->user();
        $activeBranch = $user->getActiveBranch();
        $branchFilter = ($user->isIsletme() && $activeBranch) ? $activeBranch->id : null;

        // Helper function to apply branch filter
        $applyBranchFilter = function ($query) use ($branchFilter) {
            if ($branchFilter) {
                $query->where('branch_id', $branchFilter);
            }
            return $query;
        };

        // Today's statistics
        $todayOrdersQuery = Order::today();
        $applyBranchFilter($todayOrdersQuery);

        $todayStats = [
            'orders' => (clone $todayOrdersQuery)->count(),
            'revenue' => (clone $todayOrdersQuery)->where('status', 'delivered')->sum('total'),
            'pending' => (clone $todayOrdersQuery)->where('status', 'pending')->count(),
            'delivered' => (clone $todayOrdersQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $todayOrdersQuery)->where('status', 'cancelled')->count(),
            'new_customers' => $branchFilter
                ? Customer::whereHas('orders', fn($q) => $q->where('branch_id', $branchFilter))->whereDate('created_at', today())->count()
                : Customer::whereDate('created_at', today())->count(),
        ];

        // Overall statistics
        $allOrdersQuery = Order::query();
        $applyBranchFilter($allOrdersQuery);

        $overallStats = [
            'total_orders' => (clone $allOrdersQuery)->count(),
            'total_revenue' => (clone $allOrdersQuery)->where('status', 'delivered')->sum('total'),
            'total_customers' => $branchFilter
                ? Customer::whereHas('orders', fn($q) => $q->where('branch_id', $branchFilter))->count()
                : Customer::count(),
            'total_restaurants' => Restaurant::count(),
            'total_products' => Product::count(),
            'avg_order_value' => (clone $allOrdersQuery)->where('status', 'delivered')->avg('total') ?? 0,
        ];

        // Courier statistics
        $courierStats = $this->courierAssignmentService->getCourierWorkloadStats();

        // Active orders
        $activeOrdersQuery = Order::with(['customer', 'courier', 'restaurant', 'items'])->active();
        $applyBranchFilter($activeOrdersQuery);
        $activeOrders = $activeOrdersQuery->orderBy('created_at', 'desc')->take(10)->get();

        // Recent orders
        $recentOrdersQuery = Order::with(['customer', 'courier', 'restaurant']);
        $applyBranchFilter($recentOrdersQuery);
        $recentOrders = $recentOrdersQuery->orderBy('created_at', 'desc')->take(5)->get();

        // Orders by status
        $ordersByStatusQuery = Order::select('status', DB::raw('count(*) as count'));
        $applyBranchFilter($ordersByStatusQuery);
        $ordersByStatus = $ordersByStatusQuery->groupBy('status')->pluck('count', 'status')->toArray();

        // Revenue by day (last 7 days)
        $revenueByDayQuery = Order::where('status', 'delivered')->where('created_at', '>=', now()->subDays(7));
        $applyBranchFilter($revenueByDayQuery);
        $revenueByDay = $revenueByDayQuery
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
        $topProductsQuery = DB::table('order_items')
            ->select('product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'));
        if ($branchFilter) {
            $topProductsQuery->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.branch_id', $branchFilter);
        }
        $topProducts = $topProductsQuery->groupBy('product_name')->orderByDesc('total_sold')->take(5)->get();

        // Top customers
        if ($branchFilter) {
            $topCustomers = Customer::whereHas('orders', fn($q) => $q->where('branch_id', $branchFilter))
                ->orderByDesc('total_spent')
                ->take(5)
                ->get();
        } else {
            $topCustomers = Customer::orderByDesc('total_spent')->take(5)->get();
        }

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

