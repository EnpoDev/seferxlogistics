<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategoryId = $request->get('category_id');
        
        // Get all categories with their products
        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // Get products for selected category or first category
        if ($selectedCategoryId) {
            $selectedCategory = Category::with('products')->find($selectedCategoryId);
        } else {
            $selectedCategory = $categories->first();
            if ($selectedCategory) {
                $selectedCategory->load('products');
            }
        }

        return view('pages.isletmem.menu', compact('categories', 'selectedCategory'));
    }

    public function users()
    {
        return view('pages.isletmem.kullanicilar');
    }

    public function customers()
    {
        // Get unique customers from orders
        $customers = \App\Models\Order::select('customer_name', 'customer_phone', 'customer_address')
            ->selectRaw('count(*) as order_count')
            ->selectRaw('max(created_at) as last_order_date')
            ->whereNotNull('customer_phone')
            ->groupBy('customer_phone', 'customer_name', 'customer_address') // Group by phone primarily
            ->orderBy('last_order_date', 'desc')
            ->paginate(20);

        return view('pages.isletmem.musteriler', compact('customers'));
    }

    public function couriers()
    {
        $couriers = \App\Models\Courier::orderBy('name')->get();
        return view('pages.isletmem.kuryeler', compact('couriers'));
    }

    public function menuIntegration()
    {
        return view('pages.isletmem.menu-entegrasyon');
    }
}

