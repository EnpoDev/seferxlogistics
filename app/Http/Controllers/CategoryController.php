<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with(['restaurants' => function ($query) {
                $query->where('is_active', true);
            }])
            ->withCount(['products', 'restaurants'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        
        $restaurants = Restaurant::active()->orderBy('name')->get();
        
        // Check if it's a request for the new category management page
        if ($request->routeIs('kategori.index')) {
            return view('pages.kategori.index', compact('categories', 'restaurants'));
        }
        
        return view('pages.isletmem.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('pages.isletmem.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        
        // Check if slug exists, make it unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Kategori başarıyla oluşturuldu.'
            ]);
        }

        return redirect()
            ->route('isletmem.menu')
            ->with('success', 'Kategori başarıyla oluşturuldu.');
    }

    public function edit(Category $category)
    {
        return view('pages.isletmem.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // Update slug if name changed
        if ($validated['name'] !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Check if slug exists (excluding current category)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'category' => $category->fresh(),
                'message' => 'Kategori başarıyla güncellendi.'
            ]);
        }

        return redirect()
            ->route('isletmem.menu')
            ->with('success', 'Kategori başarıyla güncellendi.');
    }

    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu kategoriye ait ürünler var. Önce ürünleri silmelisiniz.'
                ], 400);
            }
            
            return redirect()
                ->route('isletmem.menu')
                ->with('error', 'Bu kategoriye ait ürünler var. Önce ürünleri silmelisiniz.');
        }

        $category->restaurants()->detach();
        $category->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori başarıyla silindi.'
            ]);
        }

        return redirect()
            ->route('isletmem.menu')
            ->with('success', 'Kategori başarıyla silindi.');
    }

    /**
     * Sync restaurants to a category
     */
    public function syncRestaurants(Request $request, Category $category)
    {
        $validated = $request->validate([
            'restaurants' => ['required', 'array'],
            'restaurants.*' => ['exists:restaurants,id'],
        ]);

        $category->restaurants()->sync($validated['restaurants']);

        return response()->json([
            'success' => true,
            'message' => 'Restoranlar başarıyla güncellendi.',
            'restaurant_count' => $category->restaurants()->count()
        ]);
    }
}
