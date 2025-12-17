<?php

namespace App\Http\Controllers\Yonetim;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class YonetimController extends Controller
{
    public function entegrasyonlar()
    {
        return view('pages.yonetim.entegrasyonlar');
    }

    public function paketler()
    {
        return view('pages.yonetim.paketler');
    }

    public function urunler()
    {
        $products = Product::with('category')->orderBy('created_at', 'desc')->paginate(20);
        return view('pages.yonetim.urunler', compact('products'));
    }

    public function kartlar()
    {
        // Mock data for now
        $cards = collect([
            [
                'id' => 1,
                'number' => '**** **** **** 4532',
                'holder' => 'AHMET YILMAZ',
                'expiry' => '12/25',
                'is_default' => true,
                'type' => 'mastercard'
            ]
        ]);
        return view('pages.yonetim.kartlar', compact('cards'));
    }

    public function abonelikler()
    {
        // Mock data
        $subscription = [
            'plan_name' => 'Profesyonel Plan',
            'status' => 'active',
            'price' => 399,
            'period' => 'Aylık',
            'next_payment' => '15 Aralık 2025',
            'start_date' => '15 Kasım 2025'
        ];
        return view('pages.yonetim.abonelikler', compact('subscription'));
    }

    public function islemler()
    {
        // Mock data
        $transactions = collect([
            [
                'id' => 1,
                'date' => '15 Kas 2025',
                'description' => 'Profesyonel Plan - Aylık',
                'amount' => 399.00,
                'status' => 'Başarılı',
                'invoice_url' => '#'
            ],
            [
                'id' => 2,
                'date' => '15 Eki 2025',
                'description' => 'Profesyonel Plan - Aylık',
                'amount' => 399.00,
                'status' => 'Başarılı',
                'invoice_url' => '#'
            ]
        ]);
        return view('pages.yonetim.islemler', compact('transactions'));
    }
}

