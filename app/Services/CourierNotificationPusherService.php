<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\CourierNotification;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class CourierNotificationPusherService
{
    private ?Pusher $pusher = null;
    private bool $pusherConfigured = true;

    private function getPusher(): ?Pusher
    {
        if (!$this->pusherConfigured) {
            return null;
        }

        if (!$this->pusher) {
            $key = "7c2831be3c00510aff95";
            $secret = "a943657f57236bebe504";
            $appId = "2107379";

            if (empty($key) || empty($secret) || empty($appId)) {
                Log::warning('Pusher mobile credentials not configured.');
                $this->pusherConfigured = false;
                return null;
            }

            $this->pusher = new Pusher(
                $key,
                $secret,
                $appId,
                [
                    'cluster' => 'eu',
                    'useTLS' => true,
                ]
            );
        }

        return $this->pusher;
    }

    /**
     * Kuryeye bildirim gonder (DB + Pusher)
     */
    public function send(
        Courier $courier,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): CourierNotification {
        // DB'ye kaydet
        $notification = CourierNotification::create([
            'courier_id' => $courier->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
        ]);

        // Pusher ile gonder
        $pusher = $this->getPusher();
        if ($pusher) {
            try {
                $channel = 'user-' . $courier->id;
                $pusher->trigger(
                    $channel,
                    'my-event',
                    [
                        'message' => $message,
                    ]
                );
                Log::info('Pusher notification sent', [
                    'courier_id' => $courier->id,
                    'channel' => $channel,
                ]);
            } catch (\Exception $e) {
                Log::error('Pusher notification failed', [
                    'courier_id' => $courier->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    /**
     * Siparis atandiginda kurye bilgilendir
     */
    public function notifyOrderAssigned(Courier $courier, Order $order): CourierNotification
    {
        return $this->send(
            $courier,
            CourierNotification::TYPE_ORDER_ASSIGNED,
            'Yeni Siparis',
            'Size yeni bir siparis atandi.',
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_address' => $order->customer_address,
            ]
        );
    }

    /**
     * Siparis iptal edildiginde kurye bilgilendir
     */
    public function notifyOrderCancelled(Courier $courier, Order $order): CourierNotification
    {
        return $this->send(
            $courier,
            CourierNotification::TYPE_ORDER_CANCELLED,
            'Siparis Iptal',
            'Bir siparisiniz iptal edildi.',
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'cancel_reason' => $order->cancel_reason,
            ]
        );
    }

    /**
     * Havuza yeni siparis eklendiginde bildilendir
     */
    public function notifyPoolOrder(Courier $courier, Order $order): CourierNotification
    {
        return $this->send(
            $courier,
            CourierNotification::TYPE_POOL_ORDER,
            'Havuzda Yeni Siparis',
            'Havuza yeni bir siparis eklendi.',
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_address' => $order->customer_address,
                'total' => $order->total,
            ]
        );
    }

    /**
     * Sistem bildirimi gonder
     */
    public function notifySystem(Courier $courier, string $message, array $data = []): CourierNotification
    {
        return $this->send(
            $courier,
            CourierNotification::TYPE_SYSTEM,
            'Bildirim',
            $message,
            $data
        );
    }

    /**
     * Birden fazla kuryeye bildirim gonder
     */
    public function sendToMany(array $courierIds, string $message, array $data = []): void
    {
        $couriers = Courier::whereIn('id', $courierIds)->get();

        foreach ($couriers as $courier) {
            $this->notifySystem($courier, $message, $data);
        }
    }
}
