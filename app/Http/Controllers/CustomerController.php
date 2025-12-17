<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->withCount('orders')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('sort')) {
            match ($request->sort) {
                'orders' => $query->orderBy('total_orders', 'desc'),
                'spent' => $query->orderBy('total_spent', 'desc'),
                'recent' => $query->orderBy('last_order_at', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };
        }

        $customers = $query->paginate(20);

        return view('pages.musteri.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['addresses', 'orders' => function ($query) {
            $query->with(['items', 'courier', 'restaurant'])
                  ->orderBy('created_at', 'desc');
        }]);

        $stats = [
            'total_orders' => $customer->total_orders,
            'total_spent' => $customer->total_spent,
            'average_order' => $customer->total_orders > 0 
                ? $customer->total_spent / $customer->total_orders 
                : 0,
            'last_order' => $customer->last_order_at,
            'favorite_products' => $this->getFavoriteProducts($customer),
        ];

        return view('pages.musteri.show', compact('customer', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => 'Müşteri başarıyla oluşturuldu.',
            ]);
        }

        return redirect()
            ->route('musteri.show', $customer)
            ->with('success', 'Müşteri başarıyla oluşturuldu.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone,' . $customer->id],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'customer' => $customer->fresh(),
                'message' => 'Müşteri başarıyla güncellendi.',
            ]);
        }

        return redirect()
            ->route('musteri.show', $customer)
            ->with('success', 'Müşteri başarıyla güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('musteri.index')
            ->with('success', 'Müşteri başarıyla silindi.');
    }

    /**
     * Search customer by phone number (AJAX)
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:3'],
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        $customer = Customer::where('phone', 'like', "%{$phone}%")
            ->with(['addresses' => function ($query) {
                $query->orderBy('is_default', 'desc');
            }])
            ->first();

        if ($customer) {
            return response()->json([
                'found' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'formatted_phone' => $customer->formatted_phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'lat' => $customer->lat,
                    'lng' => $customer->lng,
                    'notes' => $customer->notes,
                    'total_orders' => $customer->total_orders,
                    'total_spent' => number_format($customer->total_spent, 2),
                    'last_order_at' => $customer->last_order_at?->diffForHumans(),
                    'addresses' => $customer->addresses->map(fn($addr) => [
                        'id' => $addr->id,
                        'title' => $addr->title,
                        'address' => $addr->address,
                        'full_address' => $addr->full_address,
                        'lat' => $addr->lat,
                        'lng' => $addr->lng,
                        'is_default' => $addr->is_default,
                    ]),
                ],
            ]);
        }

        return response()->json([
            'found' => false,
            'phone' => $phone,
        ]);
    }

    /**
     * Quick create customer (AJAX - from order form)
     */
    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        // Clean phone number
        $validated['phone'] = preg_replace('/[^0-9]/', '', $validated['phone']);

        // Check if customer exists
        $customer = Customer::where('phone', $validated['phone'])->first();

        if ($customer) {
            // Update existing customer
            $customer->update([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? $customer->address,
                'lat' => $validated['lat'] ?? $customer->lat,
                'lng' => $validated['lng'] ?? $customer->lng,
            ]);
        } else {
            // Create new customer
            $customer = Customer::create($validated);
        }

        return response()->json([
            'success' => true,
            'customer' => $customer,
            'is_new' => !$customer->wasRecentlyCreated,
        ]);
    }

    /**
     * Add address to customer
     */
    public function addAddress(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'building_no' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:10'],
            'apartment_no' => ['nullable', 'string', 'max:20'],
            'directions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address = $customer->addresses()->create($validated);

        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'address' => $address,
            'message' => 'Adres başarıyla eklendi.',
        ]);
    }

    /**
     * Update customer address
     */
    public function updateAddress(Request $request, CustomerAddress $address): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'building_no' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:10'],
            'apartment_no' => ['nullable', 'string', 'max:20'],
            'directions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address->update($validated);

        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'address' => $address->fresh(),
            'message' => 'Adres başarıyla güncellendi.',
        ]);
    }

    /**
     * Delete customer address
     */
    public function deleteAddress(CustomerAddress $address): JsonResponse
    {
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Adres başarıyla silindi.',
        ]);
    }

    /**
     * Get customer's order history
     */
    public function orderHistory(Customer $customer)
    {
        $orders = $customer->orders()
            ->with(['items', 'courier', 'restaurant'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.musteri.orders', compact('customer', 'orders'));
    }

    /**
     * Get favorite products for a customer
     */
    private function getFavoriteProducts(Customer $customer): array
    {
        return $customer->orders()
            ->with('items.product')
            ->get()
            ->pluck('items')
            ->flatten()
            ->groupBy('product_id')
            ->map(fn($items) => [
                'product_name' => $items->first()->product_name,
                'count' => $items->sum('quantity'),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();
    }
}

