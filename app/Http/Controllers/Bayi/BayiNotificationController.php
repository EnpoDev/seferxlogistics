<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierNotification;
use App\Services\CourierNotificationPusherService;
use Illuminate\Http\Request;

class BayiNotificationController extends Controller
{
    public function __construct(
        private CourierNotificationPusherService $notificationService
    ) {}

    /**
     * Bildirim gonderme sayfasi
     */
    public function index()
    {
        // SADECE KENDI KURYELERINI GOSTER
        $couriers = Courier::where('user_id', auth()->id())
            ->where('is_app_enabled', true)
            ->orderBy('name')
            ->get();

        $recentNotifications = CourierNotification::with('courier')
            ->whereIn('courier_id', $couriers->pluck('id'))
            ->latest()
            ->take(20)
            ->get();

        return view('bayi.kuryelere-bildirim', compact('couriers', 'recentNotifications'));
    }

    /**
     * Bildirim gonder
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'courier_ids' => 'required|array|min:1',
            'courier_ids.*' => 'exists:couriers,id',
            'message' => 'required|string|max:500',
        ]);

        // SADECE KENDI KURYELERINE BILDIRIM GONDEREBILIR
        $allowedCourierIds = Courier::where('user_id', auth()->id())
            ->whereIn('id', $validated['courier_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($allowedCourierIds)) {
            return back()->with('error', 'Secilen kuryelere erisim yetkiniz yok.');
        }

        $this->notificationService->sendToMany(
            $allowedCourierIds,
            $validated['message']
        );

        $count = count($allowedCourierIds);
        return back()->with('success', "{$count} kuryeye bildirim gonderildi.");
    }
}
