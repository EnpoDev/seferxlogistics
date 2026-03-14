<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $branchId = auth()->user()->getActiveBranchId();

        $query = Product::with('category')->orderBy('name');

        if ($branchId) {
            $query->whereHas('restaurant', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $products = $query->paginate(20);

        return view('pages.isletmem.products.index', compact('products'));
    }

    public function create()
    {
        $branchId = auth()->user()->getActiveBranchId();

        $query = Category::where('is_active', true)->orderBy('order')->orderBy('name');

        if ($branchId) {
            $query->whereHas('restaurants', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $categories = $query->get();

        return view('pages.isletmem.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'], // 2MB max
            'is_active' => ['boolean'],
            'in_stock' => ['boolean'],
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        
        // Check if slug exists, make it unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    public function edit(Product $product)
    {
        $branchId = auth()->user()->getActiveBranchId();

        $query = Category::where('is_active', true)->orderBy('order')->orderBy('name');

        if ($branchId) {
            $query->whereHas('restaurants', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $categories = $query->get();

        return view('pages.isletmem.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
            'in_stock' => ['boolean'],
        ]);

        // Update slug if name changed
        if ($validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Check if slug exists (excluding current product)
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function destroy(Product $product)
    {
        // Check if product has orders
        if ($product->orderItems()->count() > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu ürün siparişlerde kullanılıyor. Silemezsiniz.'
                ], 400);
            }

            return redirect()
                ->back()
                ->with('error', 'Bu ürün siparişlerde kullanılıyor. Silemezsiniz.');
        }

        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ürün başarıyla silindi.'
            ]);
        }

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Ürün başarıyla silindi.');
    }

    /**
     * Store product option groups and options (JSON payload)
     */
    public function storeOptionGroups(Request $request, Product $product)
    {
        $validated = $request->validate([
            'groups' => ['required', 'array'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.type' => ['required', 'in:radio,checkbox'],
            'groups.*.required' => ['boolean'],
            'groups.*.min_selections' => ['nullable', 'integer', 'min:0'],
            'groups.*.max_selections' => ['nullable', 'integer', 'min:1'],
            'groups.*.order' => ['nullable', 'integer'],
            'groups.*.options' => ['required', 'array', 'min:1'],
            'groups.*.options.*.name' => ['required', 'string', 'max:255'],
            'groups.*.options.*.price_modifier' => ['nullable', 'numeric'],
            'groups.*.options.*.is_default' => ['boolean'],
            'groups.*.options.*.is_available' => ['boolean'],
            'groups.*.options.*.order' => ['nullable', 'integer'],
        ]);

        // Delete existing groups and recreate (full replace approach)
        $product->optionGroups()->delete();

        foreach ($validated['groups'] as $groupIndex => $groupData) {
            $group = $product->optionGroups()->create([
                'name' => $groupData['name'],
                'type' => $groupData['type'],
                'required' => $groupData['required'] ?? false,
                'min_selections' => $groupData['min_selections'] ?? 0,
                'max_selections' => $groupData['max_selections'] ?? null,
                'order' => $groupData['order'] ?? $groupIndex,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                $group->options()->create([
                    'name' => $optionData['name'],
                    'price_modifier' => $optionData['price_modifier'] ?? 0,
                    'is_default' => $optionData['is_default'] ?? false,
                    'is_available' => $optionData['is_available'] ?? true,
                    'order' => $optionData['order'] ?? $optionIndex,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Varyasyon grupları başarıyla kaydedildi.',
            'groups' => $product->optionGroups()->with('options')->get(),
        ]);
    }

    /**
     * Get product option groups (for edit modal)
     */
    public function getOptionGroups(Product $product)
    {
        return response()->json([
            'success' => true,
            'groups' => $product->optionGroups()->with('options')->get(),
        ]);
    }
}

