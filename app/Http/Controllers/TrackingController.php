<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function __construct(
        private TrackingService $trackingService
    ) {}

    /**
     * Takip sayfası (public)
     */
    public function show(string $token)
    {
        $order = Order::findByTrackingToken($token);

        if (!$order) {
            abort(404, 'Sipariş bulunamadı');
        }

        $trackingData = $this->trackingService->getTrackingData($order);

        return view('tracking.show', [
            'order' => $order,
            'tracking' => $trackingData,
        ]);
    }

    /**
     * Takip verilerini JSON olarak döndür (polling için)
     */
    public function data(string $token): JsonResponse
    {
        $order = Order::findByTrackingToken($token);

        if (!$order) {
            return response()->json(['error' => 'Sipariş bulunamadı'], 404);
        }

        $trackingData = $this->trackingService->getTrackingData($order);

        return response()->json($trackingData);
    }

    /**
     * Kurye konum güncelleme (API)
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $courier = auth()->user()?->courier;

        if (!$courier) {
            return response()->json(['error' => 'Kurye bulunamadı'], 403);
        }

        $this->trackingService->updateCourierLocation(
            $courier,
            $request->lat,
            $request->lng
        );

        return response()->json(['success' => true]);
    }

    /**
     * Sipariş no ile takip (form submit)
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $query = $request->query;

        // Token ile ara
        $order = Order::where('tracking_token', strtoupper($query))->first();

        // Sipariş numarası ile ara
        if (!$order) {
            $order = Order::where('order_number', $query)->first();
        }

        // Telefon numarası ile ara (son sipariş)
        if (!$order) {
            $order = Order::where('customer_phone', 'like', '%' . $query)
                ->latest()
                ->first();
        }

        if (!$order) {
            return back()->with('error', 'Sipariş bulunamadı. Lütfen takip kodunu veya sipariş numarasını kontrol edin.');
        }

        return redirect()->route('tracking.show', $order->tracking_token);
    }

    /**
     * Takip arama sayfası
     */
    public function index()
    {
        return view('tracking.index');
    }
}
