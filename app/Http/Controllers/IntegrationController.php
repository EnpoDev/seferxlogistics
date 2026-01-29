<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\Order;
use App\Services\Integrations\YemeksepetiService;
use App\Services\Integrations\GetirService;
use App\Services\Integrations\TrendyolService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    protected array $services = [];

    public function __construct()
    {
        $this->services = [
            'yemeksepeti' => new YemeksepetiService(),
            'getir' => new GetirService(),
            'trendyol' => new TrendyolService(),
        ];
    }

    public function index()
    {
        $integrations = [];

        foreach ($this->services as $platform => $service) {
            $integration = $service->getIntegration();

            $integrations[$platform] = [
                'platform' => $platform,
                'name' => method_exists($service, 'getPlatformName') ? $service->getPlatformName() : ucfirst($platform),
                'integration' => $integration,
                'is_connected' => $integration?->is_connected ?? false,
                'status' => $integration?->status ?? 'inactive',
                'credentials' => $service->getRequiredCredentials(),
            ];
        }

        return view('pages.yonetim.entegrasyonlar', compact('integrations'));
    }

    /**
     * Entegrasyon dashboard API
     */
    public function dashboard(): JsonResponse
    {
        $integrations = Integration::all();

        $data = [];
        foreach ($integrations as $integration) {
            // Platform siparisleri
            $orderPrefix = match ($integration->platform) {
                'yemeksepeti' => 'YS-',
                'getir' => 'GT-',
                'trendyol' => 'TY-',
                default => strtoupper(substr($integration->platform, 0, 2)) . '-',
            };

            $todayOrders = Order::where('order_number', 'like', $orderPrefix . '%')
                ->whereDate('created_at', today())
                ->count();

            $weekOrders = Order::where('order_number', 'like', $orderPrefix . '%')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count();

            $totalRevenue = Order::where('order_number', 'like', $orderPrefix . '%')
                ->where('status', 'delivered')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('total');

            $data[] = [
                'platform' => $integration->platform,
                'name' => $integration->getPlatformLabel(),
                'is_connected' => $integration->is_connected,
                'status' => $integration->status,
                'status_label' => $integration->getStatusLabel(),
                'status_color' => $integration->getStatusColor(),
                'last_sync_at' => $integration->last_sync_at?->diffForHumans(),
                'error_message' => $integration->error_message,
                'today_orders' => $todayOrders,
                'week_orders' => $weekOrders,
                'month_revenue' => $totalRevenue,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Entegrasyon istatistikleri
     */
    public function stats(string $platform): JsonResponse
    {
        if (!isset($this->services[$platform])) {
            return response()->json(['success' => false, 'message' => __('messages.error.invalid_platform')], 400);
        }

        $orderPrefix = match ($platform) {
            'yemeksepeti' => 'YS-',
            'getir' => 'GT-',
            'trendyol' => 'TY-',
            default => strtoupper(substr($platform, 0, 2)) . '-',
        };

        // Gunluk siparis trendi (son 7 gun)
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Order::where('order_number', 'like', $orderPrefix . '%')
                ->whereDate('created_at', $date)
                ->count();
            $revenue = Order::where('order_number', 'like', $orderPrefix . '%')
                ->whereDate('created_at', $date)
                ->where('status', 'delivered')
                ->sum('total');

            $dailyStats[] = [
                'date' => now()->subDays($i)->format('d.m'),
                'orders' => $count,
                'revenue' => $revenue,
            ];
        }

        // Durum dagilimi
        $statusStats = Order::where('order_number', 'like', $orderPrefix . '%')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'daily' => $dailyStats,
                'status' => $statusStats,
            ],
        ]);
    }

    public function connect(Request $request, string $platform)
    {
        if (!isset($this->services[$platform])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_platform'),
            ], 400);
        }

        $service = $this->services[$platform];
        $credentials = $request->input('credentials', []);

        // Validate required credentials
        foreach ($service->getRequiredCredentials() as $key => $config) {
            if ($config['required'] && empty($credentials[$key])) {
                return response()->json([
                    'success' => false,
                    'message' => "{$config['label']} zorunludur.",
                ], 422);
            }
        }

        $success = $service->connect($credentials);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Bağlantı başarılı!' : 'Bağlantı kurulamadı.',
        ]);
    }

    public function disconnect(string $platform)
    {
        if (!isset($this->services[$platform])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_platform'),
            ], 400);
        }

        $success = $this->services[$platform]->disconnect();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Bağlantı kesildi.' : 'İşlem başarısız.',
        ]);
    }

    public function testConnection(Request $request, string $platform)
    {
        if (!isset($this->services[$platform])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_platform'),
            ], 400);
        }

        $credentials = $request->input('credentials', []);
        $success = $this->services[$platform]->testConnection($credentials);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Bağlantı testi başarılı!' : 'Bağlantı testi başarısız.',
        ]);
    }

    public function webhook(Request $request, string $platform, string $token)
    {
        $integration = Integration::where('platform', $platform)
            ->where('webhook_secret', $token)
            ->first();

        if (!$integration) {
            return response()->json(['error' => 'Invalid webhook'], 401);
        }

        if (isset($this->services[$platform])) {
            $this->services[$platform]->handleWebhook($request->all());
        }

        return response()->json(['success' => true]);
    }

    public function syncOrders(string $platform)
    {
        if (!isset($this->services[$platform])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_platform'),
            ], 400);
        }

        $orders = $this->services[$platform]->fetchOrders();

        return response()->json([
            'success' => true,
            'message' => count($orders) . ' sipariş senkronize edildi.',
            'count' => count($orders),
        ]);
    }
}

