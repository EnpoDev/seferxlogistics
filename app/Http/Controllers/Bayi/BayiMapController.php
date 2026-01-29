<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;

class BayiMapController extends Controller
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
}
