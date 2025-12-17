<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\Integrations\YemeksepetiService;
use App\Services\Integrations\GetirService;
use App\Services\Integrations\TrendyolService;
use Illuminate\Http\Request;

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
                'name' => $service->getPlatformName ?? ucfirst($platform),
                'integration' => $integration,
                'is_connected' => $integration?->is_connected ?? false,
                'status' => $integration?->status ?? 'inactive',
                'credentials' => $service->getRequiredCredentials(),
            ];
        }

        return view('pages.yonetim.entegrasyonlar', compact('integrations'));
    }

    public function connect(Request $request, string $platform)
    {
        if (!isset($this->services[$platform])) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz platform.',
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
                'message' => 'Geçersiz platform.',
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
                'message' => 'Geçersiz platform.',
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
                'message' => 'Geçersiz platform.',
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

