<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BayiSettingsController extends Controller
{
    public function ayarlarGenel()
    {
        $user = auth()->user();
        return view('bayi.ayarlar.genel', compact('user'));
    }

    public function updateGenel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $user->update($validated);

        return back()->with('success', __('messages.success.general_settings_updated'));
    }

    public function ayarlarKurye()
    {
        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        $settings = $branch ? \App\Models\BranchSetting::getOrCreateForBranch($branch->id) : null;
        return view('bayi.ayarlar.kurye', compact('settings'));
    }

    public function updateKurye(Request $request)
    {
        $validated = $request->validate([
            'auto_assign_courier' => 'nullable|boolean',
            'check_courier_shift' => 'nullable|boolean',
            'max_delivery_time' => 'required|integer|min:10|max:180',
        ]);

        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        if (!$branch) {
            return back()->with('error', __('messages.error.branch_not_found'));
        }

        $settings = \App\Models\BranchSetting::getOrCreateForBranch($branch->id);
        $settings->update([
            'auto_assign_courier' => $request->boolean('auto_assign_courier'),
            'check_courier_shift' => $request->boolean('check_courier_shift'),
            'max_delivery_time' => $validated['max_delivery_time'],
        ]);

        return back()->with('success', __('messages.success.courier_settings_updated'));
    }

    public function ayarlarUygulama()
    {
        $settings = \App\Models\ApplicationSetting::getOrCreateForUser(auth()->id());
        return view('bayi.ayarlar.uygulama', compact('settings'));
    }

    public function updateUygulama(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|in:tr,en',
            'timezone' => 'required|string',
            'sound_notifications' => 'nullable|boolean',
        ]);

        $settings = \App\Models\ApplicationSetting::getOrCreateForUser(auth()->id());
        $settings->update([
            'language' => $validated['language'],
            'timezone' => $validated['timezone'],
            'sound_notifications' => $request->boolean('sound_notifications'),
        ]);

        return back()->with('success', __('messages.success.app_settings_updated'));
    }

    public function ayarlarHavuz()
    {
        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        $settings = $branch ? \App\Models\BranchSetting::getOrCreateForBranch($branch->id) : null;

        return view('bayi.ayarlar.havuz', compact('settings'));
    }

    public function updateHavuz(Request $request)
    {
        $validated = $request->validate([
            'pool_enabled' => 'nullable|boolean',
            'pool_wait_time' => 'required|integer|min:1|max:60',
            'pool_auto_assign' => 'nullable|boolean',
            'pool_ai_distribution' => 'nullable|boolean',
            'pool_max_orders' => 'required|integer|min:1|max:100',
            'pool_priority_by_distance' => 'nullable|boolean',
            'pool_notify_couriers' => 'nullable|boolean',
        ]);

        $branch = \App\Models\Branch::whereNull('parent_id')->first();

        if (!$branch) {
            return back()->with('error', __('messages.error.branch_not_found'));
        }

        $settings = \App\Models\BranchSetting::getOrCreateForBranch($branch->id);

        $settings->update([
            'pool_enabled' => $request->boolean('pool_enabled'),
            'pool_wait_time' => $validated['pool_wait_time'],
            'pool_auto_assign' => $request->boolean('pool_auto_assign'),
            'pool_ai_distribution' => $request->boolean('pool_ai_distribution'),
            'pool_max_orders' => $validated['pool_max_orders'],
            'pool_priority_by_distance' => $request->boolean('pool_priority_by_distance'),
            'pool_notify_couriers' => $request->boolean('pool_notify_couriers'),
        ]);

        return back()->with('success', __('messages.success.pool_settings_updated'));
    }

    public function ayarlarBildirim()
    {
        $settings = \App\Models\NotificationSetting::getOrCreateForUser(auth()->id());
        return view('bayi.ayarlar.bildirim', compact('settings'));
    }

    public function updateBildirim(Request $request)
    {
        $settings = \App\Models\NotificationSetting::getOrCreateForUser(auth()->id());
        $settings->update([
            'new_order_notification' => $request->boolean('new_order_notification'),
            'order_status_notification' => $request->boolean('order_status_notification'),
            'email_new_order' => $request->boolean('email_new_order'),
            'sms_enabled' => $request->boolean('sms_enabled'),
        ]);

        return back()->with('success', __('messages.success.notification_settings_updated'));
    }

    public function tema()
    {
        $themeSettings = \App\Models\ThemeSetting::getOrCreateForUser(auth()->id());
        return view('bayi.tema', compact('themeSettings'));
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_mode' => 'required|in:light,dark,system',
            'accent_color' => 'nullable|string|max:20',
            'compact_mode' => 'nullable|boolean',
            'animations_enabled' => 'nullable|boolean',
            'sidebar_auto_hide' => 'nullable|boolean',
            'sidebar_width' => 'nullable|in:narrow,normal,wide',
        ]);

        $themeSettings = \App\Models\ThemeSetting::getOrCreateForUser(auth()->id());
        $themeSettings->update([
            'theme_mode' => $validated['theme_mode'],
            'accent_color' => $validated['accent_color'] ?? '#000000',
            'compact_mode' => $request->boolean('compact_mode'),
            'animations_enabled' => $request->boolean('animations_enabled'),
            'sidebar_auto_hide' => $request->boolean('sidebar_auto_hide'),
            'sidebar_width' => $validated['sidebar_width'] ?? 'normal',
        ]);

        return redirect()->route('bayi.tema')->with('success', __('messages.success.theme_updated'));
    }

    // ============================================
    // TRENDYOL GO ENTEGRASYONU
    // ============================================

    public function ayarlarTrendyol()
    {
        $trendyolService = $this->getTrendyolService();

        $restaurant = null;
        $deliveryAreas = null;
        $menu = null;

        if ($trendyolService) {
            try {
                $restaurant = $trendyolService->getRestaurantInfo();
                $deliveryAreas = $trendyolService->getDeliveryAreas();
                $menu = $trendyolService->getMenuProducts();
            } catch (\Exception $e) {
                \Log::error("[Trendyol Settings] Error: " . $e->getMessage());
            }
        }

        return view('bayi.ayarlar.trendyol', compact('restaurant', 'deliveryAreas', 'menu'));
    }

    public function updateTrendyolStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:OPEN,CLOSED',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return back()->with('error', __('messages.error.integration_not_found'));
        }

        $success = $trendyolService->updateWorkingStatus($request->status);

        if ($success) {
            return back()->with('success', __('messages.success.restaurant_status_updated'));
        }

        return back()->with('error', __('messages.error.status_update_failed'));
    }

    public function updateTrendyolDeliveryTime(Request $request)
    {
        $request->validate([
            'min' => 'required|integer|min:15|max:85',
            'max' => 'required|integer|min:20|max:90',
        ]);

        if ($request->min >= $request->max) {
            return back()->with('error', __('messages.error.min_time_greater_than_max'));
        }

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return back()->with('error', __('messages.error.integration_not_found'));
        }

        $success = $trendyolService->updateDeliveryTime($request->min, $request->max);

        if ($success) {
            return back()->with('success', __('messages.success.delivery_time_updated'));
        }

        return back()->with('error', __('messages.error.delivery_time_update_failed'));
    }

    public function updateTrendyolWorkingHours(Request $request)
    {
        $days = $request->input('days', []);
        $workingHours = [];

        foreach ($days as $day => $data) {
            if (!empty($data['enabled'])) {
                $workingHours[] = [
                    'dayOfWeek' => $day,
                    'openingTime' => ($data['open'] ?? '09:00') . ':00',
                    'closingTime' => ($data['close'] ?? '22:00') . ':00',
                ];
            }
        }

        if (empty($workingHours)) {
            return back()->with('error', __('messages.error.select_at_least_one_day'));
        }

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return back()->with('error', __('messages.error.integration_not_found'));
        }

        $success = $trendyolService->updateWorkingHours($workingHours);

        if ($success) {
            return back()->with('success', __('messages.success.working_hours_updated'));
        }

        return back()->with('error', __('messages.error.working_hours_update_failed'));
    }

    public function updateTrendyolSectionStatus(Request $request)
    {
        $request->validate([
            'section_name' => 'required|string',
            'status' => 'required|in:ACTIVE,PASSIVE',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return back()->with('error', __('messages.error.integration_not_found'));
        }

        $success = $trendyolService->updateSectionStatus($request->section_name, $request->status);

        if ($success) {
            $statusText = $request->status === 'ACTIVE' ? __('ui.status.opened') : __('ui.status.closed');
            return back()->with('success', "'{$request->section_name}' {$statusText}.");
        }

        return back()->with('error', __('messages.error.category_status_update_failed'));
    }

    public function updateTrendyolProductStatus(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'status' => 'required|in:ACTIVE,PASSIVE',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return back()->with('error', __('messages.error.integration_not_found'));
        }

        $success = $trendyolService->updateProductStatus($request->product_id, $request->status);

        if ($success) {
            $statusText = $request->status === 'ACTIVE' ? __('ui.status.opened') : __('ui.status.closed');
            return back()->with('success', __('messages.success.product_updated') . " ({$statusText})");
        }

        return back()->with('error', __('messages.error.product_status_update_failed'));
    }

    // Trendyol Order Management
    public function getTrendyolOrders(Request $request)
    {
        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        $statuses = $request->input('statuses', ['Created', 'Picking', 'Invoiced', 'Shipped']);

        try {
            $orders = $trendyolService->fetchOrdersByStatuses($statuses);

            $parsedOrders = [];
            foreach ($orders as $order) {
                $parsedOrders[] = [
                    'id' => $order['id'] ?? null,
                    'orderNumber' => $order['orderNumber'] ?? null,
                    'orderId' => $order['orderId'] ?? null,
                    'status' => $order['packageStatus'] ?? 'Unknown',
                    'statusLabel' => $this->getTrendyolStatusLabel($order['packageStatus'] ?? ''),
                    'totalPrice' => $order['totalPrice'] ?? 0,
                    'customerName' => ($order['address']['firstName'] ?? '') . ' ' . ($order['address']['lastName'] ?? ''),
                    'customerPhone' => $order['address']['phone'] ?? '',
                    'address' => $this->formatTrendyolAddressShort($order['address'] ?? []),
                    'createdAt' => $order['packageCreationDate'] ?? null,
                    'preparationTime' => $order['preparationTime'] ?? null,
                    'lines' => collect($order['lines'] ?? [])->map(fn($line) => [
                        'name' => $line['name'] ?? '',
                        'price' => $line['price'] ?? 0,
                        'quantity' => count($line['items'] ?? []) ?: 1,
                    ])->toArray(),
                    'itemIds' => $trendyolService->extractPackageItemIds($order),
                ];
            }

            return response()->json([
                'orders' => $parsedOrders,
                'count' => count($parsedOrders),
            ]);
        } catch (\Exception $e) {
            \Log::error("[Trendyol Orders] Error: " . $e->getMessage());
            return response()->json(['error' => 'Siparişler alınamadı: ' . $e->getMessage()], 500);
        }
    }

    public function acceptTrendyolOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
            'preparation_time' => 'nullable|integer|min:5|max:60',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        $preparationTime = $request->input('preparation_time', 15);

        try {
            $success = $trendyolService->acceptOrderByPackageId($request->package_id, $preparationTime);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Sipariş kabul edildi']);
            }

            return response()->json(['error' => 'Sipariş kabul edilemedi'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function prepareTrendyolOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        try {
            $success = $trendyolService->markPreparedByPackageId($request->package_id);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Sipariş hazır olarak işaretlendi']);
            }

            return response()->json(['error' => 'İşlem başarısız'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function shipTrendyolOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        try {
            $actualDate = now()->timestamp * 1000;
            $success = $trendyolService->markShippedByPackageId($request->package_id, $actualDate);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Sipariş yola çıktı']);
            }

            return response()->json(['error' => 'İşlem başarısız'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deliverTrendyolOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        try {
            $actualDate = now()->timestamp * 1000;
            $success = $trendyolService->markDeliveredByPackageId($request->package_id, $actualDate);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Sipariş teslim edildi']);
            }

            return response()->json(['error' => 'İşlem başarısız'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelTrendyolOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
            'item_ids' => 'required|array',
            'reason_id' => 'required|integer',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        try {
            $success = $trendyolService->cancelOrderByPackageId(
                $request->package_id,
                $request->item_ids,
                $request->reason_id
            );

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Sipariş iptal edildi']);
            }

            return response()->json(['error' => 'İptal işlemi başarısız'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendTrendyolInvoice(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'invoice_link' => 'required|url',
        ]);

        $trendyolService = $this->getTrendyolService();

        if (!$trendyolService) {
            return response()->json(['error' => 'Trendyol entegrasyonu bulunamadı'], 404);
        }

        try {
            $success = $trendyolService->sendInvoiceLinkByOrderId($request->order_id, $request->invoice_link);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Fatura linki gönderildi']);
            }

            return response()->json(['error' => 'Fatura gönderimi başarısız'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function getTrendyolStatusLabel(string $status): string
    {
        return match ($status) {
            'Created' => 'Yeni Sipariş',
            'Picking' => 'Hazırlanıyor',
            'Invoiced' => 'Hazır',
            'Shipped' => 'Yolda',
            'Delivered' => 'Teslim Edildi',
            'Cancelled' => 'İptal Edildi',
            'UnSupplied' => 'Tedarik Edilemedi',
            default => $status,
        };
    }

    protected function formatTrendyolAddressShort(array $address): string
    {
        $parts = array_filter([
            $address['neighborhood'] ?? '',
            $address['district'] ?? '',
        ]);

        return implode(', ', $parts) ?: ($address['address1'] ?? 'Adres yok');
    }

    protected function getTrendyolService(): ?\App\Services\Integrations\TrendyolService
    {
        $integration = \App\Models\Integration::where('platform', 'trendyol')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return null;
        }

        $service = new \App\Services\Integrations\TrendyolService($integration->branch);

        // Integration'ı service'e set et
        $reflector = new \ReflectionClass($service);
        if ($reflector->hasProperty('integration')) {
            $property = $reflector->getProperty('integration');
            $property->setAccessible(true);
            $property->setValue($service, $integration);
        }

        return $service;
    }
}
