<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallerIdController extends Controller
{
    /**
     * Lookup customer by phone number for Caller ID app
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        // Normalize phone number (remove non-digits)
        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        // Handle Turkish phone formats
        // 05551234567 -> 5551234567
        // +905551234567 -> 5551234567
        // 905551234567 -> 5551234567
        if (strlen($phone) === 12 && str_starts_with($phone, '90')) {
            $phone = substr($phone, 2);
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Find customer by phone
        $customer = Customer::where('phone', $phone)
            ->orWhere('phone', '0' . $phone)
            ->orWhere('phone', '90' . $phone)
            ->orWhere('phone', '+90' . $phone)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Müşteri bulunamadı',
                'customer' => null,
            ], 404);
        }

        // Load recent orders (last 10)
        $recentOrders = $customer->orders()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->order_number,
                    'order_date' => $order->created_at->toIso8601String(),
                    'total_amount' => (float) $order->total,
                    'items' => $order->items->map(fn($item) => $item->product_name)->toArray(),
                    'status' => $this->translateStatus($order->status),
                ];
            });

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'phone_number' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'total_orders' => (int) $customer->total_orders,
                'total_spent' => (float) $customer->total_spent,
                'last_order_date' => $customer->last_order_at?->toIso8601String(),
                'notes' => $customer->notes,
                'customer_type' => $customer->customer_type,
                'preferred_contact_method' => $customer->preferred_contact_method ?? 'Telefon',
                'order_history' => $recentOrders,
            ],
            'metadata' => [
                'loyalty_score' => $customer->loyalty_score,
                'average_order_value' => $customer->average_order_value,
                'days_since_last_order' => $customer->days_since_last_order,
            ],
        ]);
    }

    /**
     * Sync customers for Caller ID app (batch)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'updated_since' => 'nullable|date',
        ]);

        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 50);

        $query = Customer::query()
            ->orderBy('updated_at', 'desc');

        // Filter by updated_since for incremental sync
        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->updated_since);
        }

        $customers = $query->paginate($perPage, ['*'], 'page', $page);

        $customerData = $customers->map(function ($customer) {
            return [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'phone_number' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'total_orders' => (int) $customer->total_orders,
                'total_spent' => (float) $customer->total_spent,
                'last_order_date' => $customer->last_order_at?->toIso8601String(),
                'notes' => $customer->notes,
                'customer_type' => $customer->customer_type,
                'preferred_contact_method' => $customer->preferred_contact_method ?? 'Telefon',
            ];
        });

        return response()->json([
            'success' => true,
            'customers' => $customerData,
            'metadata' => [
                'total_count' => $customers->total(),
                'page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'last_page' => $customers->lastPage(),
                'has_more' => $customers->hasMorePages(),
            ],
        ]);
    }

    /**
     * Translate order status to Turkish
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Bekliyor',
            'preparing' => 'Hazırlanıyor',
            'ready' => 'Hazır',
            'on_delivery' => 'Yolda',
            'delivered' => 'Tamamlandı',
            'cancelled' => 'İptal',
            'returned' => 'İade',
            default => $status,
        };
    }
}
