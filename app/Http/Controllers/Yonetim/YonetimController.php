<?php

namespace App\Http\Controllers\Yonetim;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Http\Request;

class YonetimController extends Controller
{
    public function entegrasyonlar()
    {
        return view('pages.yonetim.entegrasyonlar');
    }

    public function paketler()
    {
        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentSubscription = auth()->user()->subscriptions()
            ->with('plan')
            ->valid()
            ->first();

        return view('pages.yonetim.paketler', compact('plans', 'currentSubscription'));
    }

    public function urunler()
    {
        $products = Product::with('category')->orderBy('created_at', 'desc')->paginate(20);
        return view('pages.yonetim.urunler', compact('products'));
    }

    public function kartlar()
    {
        $cards = auth()->user()->paymentCards()->orderBy('is_default', 'desc')->get();
        return view('pages.yonetim.kartlar', compact('cards'));
    }

    public function abonelikler()
    {
        $subscription = auth()->user()->subscriptions()
            ->with(['plan', 'paymentCard'])
            ->latest()
            ->first();
            
        return view('pages.yonetim.abonelikler', compact('subscription'));
    }

    public function islemler(Request $request)
    {
        $query = auth()->user()->transactions()
            ->with(['subscription.plan', 'paymentCard'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $transactions = $query->paginate(20);

        return view('pages.yonetim.islemler', compact('transactions'));
    }
}

