<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BayiController extends Controller
{
    /**
     * Get branch IDs that belong to the authenticated bayi
     */
    private function getBayiBranchIds(): array
    {
        $bayiId = auth()->id();

        // Get işletme users under this bayi
        $isletmeUserIds = User::where('parent_id', $bayiId)
            ->whereJsonContains('roles', 'isletme')
            ->pluck('id')
            ->toArray();

        // Get branches owned by bayi or their işletmeler
        return Branch::whereIn('user_id', array_merge([$bayiId], $isletmeUserIds))
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if an order belongs to this bayi's branches
     */
    private function checkOrderOwnership(Order $order): void
    {
        $branchIds = $this->getBayiBranchIds();

        if (!in_array($order->branch_id, $branchIds)) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }
    }

    public function gecmisSiparisler()
    {
        $branchIds = $this->getBayiBranchIds();

        $orders = Order::with(['courier', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bayi.siparisler.gecmis', compact('orders'));
    }

    public function bedelsizIstekler()
    {
        $branchIds = $this->getBayiBranchIds();

        $orders = Order::with(['courier', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->where('total', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bayi.siparisler.bedelsiz', compact('orders'));
    }

    public function bedelsizApprove(Order $order)
    {
        $this->checkOrderOwnership($order);

        $order->update(['status' => 'approved']);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => __('messages.success.request_approved')]);
        }

        return back()->with('success', __('messages.success.request_approved'));
    }

    public function bedelsizReject(Order $order)
    {
        $this->checkOrderOwnership($order);

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
