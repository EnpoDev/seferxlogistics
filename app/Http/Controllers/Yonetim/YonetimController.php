<?php

namespace App\Http\Controllers\Yonetim;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

    public function urunler(Request $request)
    {
        $query = Product::with('category');

        // Arama
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Kategori filtresi
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Durum filtresi
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('pages.yonetim.urunler', compact('products', 'categories'));
    }

    public function urunStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
            'in_stock' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['in_stock'] = $request->boolean('in_stock', true);

        // Unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('yonetim.urunler')->with('success', 'Ürün başarıyla eklendi.');
    }

    public function urunUpdate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
            'in_stock' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['in_stock'] = $request->boolean('in_stock');

        // Update slug if name changed
        if ($validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']);
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter++;
            }
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('yonetim.urunler')->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function urunDestroy(Product $product)
    {
        if ($product->orderItems()->count() > 0) {
            return redirect()->back()->with('error', 'Bu ürün siparişlerde kullanılıyor, silemezsiniz.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('yonetim.urunler')->with('success', 'Ürün başarıyla silindi.');
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

