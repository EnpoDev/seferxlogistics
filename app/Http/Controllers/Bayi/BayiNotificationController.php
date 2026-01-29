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
        $couriers = Courier::where('is_app_enabled', true)
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

        $this->notificationService->sendToMany(
            $validated['courier_ids'],
            $validated['message']
        );

        $count = count($validated['courier_ids']);
        return back()->with('success', "{$count} kuryeye bildirim gonderildi.");
    }
}
