<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\Request;

class BayiController extends Controller
{
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
            return response()->json(['success' => true, 'message' => __('messages.success.request_approved')]);
        }

        return back()->with('success', __('messages.success.request_approved'));
    }

    public function bedelsizReject(Order $order)
    {
        $order->update(['status' => 'cancelled']);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => __('messages.success.request_rejected')]);
        }

        return back()->with('success', __('messages.success.request_rejected'));
    }

    public function yardim()
    {
        return view('bayi.yardim');
    }

    public function paketler()
    {
        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentSubscription = auth()->user()->subscriptions()
            ->with('plan')
            ->valid()
            ->first();

        return view('bayi.paketler', compact('plans', 'currentSubscription'));
    }
}
