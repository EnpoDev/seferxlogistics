<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $query = Restaurant::with('categories')
            ->withCount('products')
            ->orderBy('order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        $restaurants = $query->paginate(20);
        $categories = Category::active()->ordered()->get();

        return view('pages.restoran.index', compact('restaurants', 'categories'));
    }

    public function create()
    {
        $categories = Category::active()->ordered()->get();
        return view('pages.restoran.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'max_delivery_time' => ['nullable', 'integer', 'min:1'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'working_hours' => ['nullable', 'array'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('restaurants/banners', 'public');
        }

        $restaurant = Restaurant::create($validated);

        // Sync categories
        if ($request->has('categories')) {
            $restaurant->categories()->sync($request->categories);
        }

        return redirect()
            ->route('restoran.index')
            ->with('success', 'Restoran başarıyla oluşturuldu.');
    }

    public function show(Restaurant $restaurant)
    {
        $restaurant->load(['categories', 'products.category', 'orders' => function ($query) {
            $query->latest()->take(10);
        }]);

        $stats = [
            'total_orders' => $restaurant->orders()->count(),
            'total_revenue' => $restaurant->orders()->where('status', 'delivered')->sum('total'),
            'active_products' => $restaurant->products()->where('is_active', true)->count(),
            'average_rating' => $restaurant->rating,
        ];

        return view('pages.restoran.show', compact('restaurant', 'stats'));
    }

    public function edit(Restaurant $restaurant)
    {
        $categories = Category::active()->ordered()->get();
        $restaurant->load('categories');
        
        return view('pages.restoran.edit', compact('restaurant', 'categories'));
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'max_delivery_time' => ['nullable', 'integer', 'min:1'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'working_hours' => ['nullable', 'array'],
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('restaurants/banners', 'public');
        }

        $restaurant->update($validated);

        // Sync categories
        if ($request->has('categories')) {
            $restaurant->categories()->sync($request->categories);
        }

        return redirect()
            ->route('restoran.index')
            ->with('success', 'Restoran başarıyla güncellendi.');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->categories()->detach();
        $restaurant->delete();

        return redirect()
            ->route('restoran.index')
            ->with('success', 'Restoran başarıyla silindi.');
    }

    public function toggleFeatured(Restaurant $restaurant)
    {
        $restaurant->update([
            'is_featured' => !$restaurant->is_featured
        ]);

        return response()->json([
            'success' => true,
            'is_featured' => $restaurant->is_featured,
            'message' => $restaurant->is_featured 
                ? 'Restoran öne çıkarıldı.' 
                : 'Restoran öne çıkarılmaktan kaldırıldı.'
        ]);
    }

    public function syncCategories(Request $request, Restaurant $restaurant)
    {
        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*' => ['exists:categories,id'],
        ]);

        $restaurant->categories()->sync($validated['categories']);

        return response()->json([
            'success' => true,
            'message' => 'Kategoriler güncellendi.'
        ]);
    }
}

