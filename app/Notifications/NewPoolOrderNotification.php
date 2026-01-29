<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewPoolOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add FCM if device token exists and FCM is configured
        if ($notifiable->device_token && config('services.firebase.server_key')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'pool_order',
            'title' => 'Yeni Havuz Siparisi',
            'body' => sprintf(
                'Yeni siparis: %s - %s TL',
                $this->order->order_number,
                number_format($this->order->total, 2, ',', '.')
            ),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'customer_address' => $this->order->customer_address,
            'action_url' => route('kurye.pool'),
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'notification' => [
                'title' => 'Yeni Sipariş!',
                'body' => sprintf(
                    '#%s - %s TL - %s',
                    $this->order->order_number,
                    number_format($this->order->total, 2, ',', '.'),
                    \Illuminate\Support\Str::limit($this->order->customer_address, 40)
                ),
                'sound' => 'pool_order',
            ],
            'data' => [
                'type' => 'pool_order',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'total' => (string) $this->order->total,
                'action' => 'open_pool',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Yeni Havuz Siparisi',
            'body' => sprintf(
                '%s - %s TL',
                $this->order->order_number,
                number_format($this->order->total, 2, ',', '.')
            ),
            'data' => [
                'type' => 'pool_order',
                'order_id' => $this->order->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }
}
