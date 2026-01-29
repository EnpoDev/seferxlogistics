<?php

namespace App\Notifications\Channels;

use App\Services\FcmPushService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    protected FcmPushService $fcmService;

    public function __construct(FcmPushService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Check if notifiable has device token
        if (!$notifiable->device_token) {
            return;
        }

        // Check if FCM is configured
        if (!$this->fcmService->isConfigured()) {
            return;
        }

        // Get FCM data from notification
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $data = $notification->toFcm($notifiable);

        if (empty($data)) {
            return;
        }

        $this->fcmService->sendToDevice(
            $notifiable->device_token,
            $data['notification'] ?? [],
            $data['data'] ?? []
        );
    }
}
