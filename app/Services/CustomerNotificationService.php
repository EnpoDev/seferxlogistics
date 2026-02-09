<?php

namespace App\Services;

use App\Models\Order;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CustomerNotificationService
{
    // Bildirim şablonları
    private const TEMPLATES = [
        'order_confirmed' => [
            'title' => 'Sipariş Onaylandı',
            'sms' => 'Siparişiniz onaylandı! Takip kodu: {tracking_code}. Takip için: {tracking_url}',
            'whatsapp' => "Merhaba {customer_name}! 👋\n\nSiparişiniz onaylandı ✅\n\nSipariş No: #{order_number}\nTakip Kodu: {tracking_code}\n\nTakip etmek için: {tracking_url}",
        ],
        'order_preparing' => [
            'title' => 'Sipariş Hazırlanıyor',
            'sms' => 'Siparişiniz hazırlanmaya başlandı. Takip: {tracking_url}',
            'whatsapp' => "🍳 Siparişiniz hazırlanmaya başlandı!\n\nSipariş No: #{order_number}\nTakip: {tracking_url}",
        ],
        'order_ready' => [
            'title' => 'Sipariş Hazır',
            'sms' => 'Siparişiniz hazır, kurye yola çıkmak üzere. Takip: {tracking_url}',
            'whatsapp' => "✨ Siparişiniz hazır!\n\nKurye yakında yola çıkacak.\nTakip: {tracking_url}",
        ],
        'courier_assigned' => [
            'title' => 'Kurye Atandı',
            'sms' => 'Kuryeniz {courier_name} siparişinizi teslim alacak. Takip: {tracking_url}',
            'whatsapp' => "🛵 Kuryeniz atandı!\n\nKurye: {courier_name}\nTahmini süre: {eta} dk\n\nCanlı takip: {tracking_url}",
        ],
        'order_picked_up' => [
            'title' => 'Sipariş Yola Çıktı',
            'sms' => 'Siparişiniz yola çıktı! Tahmini varış: {eta} dk. Takip: {tracking_url}',
            'whatsapp' => "🚀 Siparişiniz yola çıktı!\n\nKurye: {courier_name}\nTahmini varış: {eta} dakika\n\nCanlı takip: {tracking_url}",
        ],
        'order_delivered' => [
            'title' => 'Sipariş Teslim Edildi',
            'sms' => 'Siparişiniz teslim edildi! Bizi tercih ettiğiniz için teşekkürler.',
            'whatsapp' => "🎉 Siparişiniz teslim edildi!\n\nBizi tercih ettiğiniz için teşekkürler. Afiyet olsun! 😊\n\nDeğerlendirmenizi bekliyoruz: {review_url}",
        ],
        'order_cancelled' => [
            'title' => 'Sipariş İptal Edildi',
            'sms' => 'Siparişiniz iptal edildi. Detaylı bilgi için bizi arayın.',
            'whatsapp' => "❌ Siparişiniz iptal edildi.\n\nSipariş No: #{order_number}\nSebep: {cancel_reason}\n\nSorularınız için: {support_phone}",
        ],
        'courier_arrived' => [
            'title' => 'Kurye Kapıda',
            'sms' => 'Kuryeniz kapınıza ulaştı! Lütfen teslim almaya hazır olun.',
            'whatsapp' => "🔔 Kuryeniz kapınıza ulaştı!\n\nKurye: {courier_name}\nSipariş No: #{order_number}\n\nLütfen teslim almaya hazır olun.",
        ],
    ];

    /**
     * Sipariş durumu değişikliğinde bildirim gönder
     */
    public function sendStatusNotification(Order $order, string $status): void
    {
        $templateKey = $this->getTemplateKeyForStatus($status);

        if (!$templateKey) {
            return;
        }

        $settings = $this->getNotificationSettings($order->branch_id);

        // Bu durum için bildirim etkin mi kontrol et
        if (!$this->shouldNotifyForStatus($status, $settings)) {
            return;
        }

        // SMS gönder
        if ($settings['sms_enabled'] ?? false) {
            $this->sendSMS($order, $templateKey);
        }

        // WhatsApp gönder
        if ($settings['whatsapp_enabled'] ?? false) {
            $this->sendWhatsApp($order, $templateKey);
        }
    }

    /**
     * Bu durum için bildirim gönderilmeli mi?
     */
    private function shouldNotifyForStatus(string $status, array $settings): bool
    {
        return match ($status) {
            'pending' => $settings['notify_on_confirmed'] ?? true,
            'preparing' => $settings['notify_on_preparing'] ?? false,
            'ready' => $settings['notify_on_ready'] ?? false,
            'on_delivery' => $settings['notify_on_picked_up'] ?? true,
            'delivered' => $settings['notify_on_delivered'] ?? true,
            'cancelled' => $settings['notify_on_cancelled'] ?? true,
            default => false,
        };
    }

    /**
     * Kurye atandığında bildirim gönder (ayrı metod)
     */
    public function sendCourierAssignedNotification(Order $order): void
    {
        $settings = $this->getNotificationSettings($order->branch_id);

        if (!($settings['notify_on_courier_assigned'] ?? true)) {
            return;
        }

        if ($settings['sms_enabled'] ?? false) {
            $this->sendSMS($order, 'courier_assigned');
        }

        if ($settings['whatsapp_enabled'] ?? false) {
            $this->sendWhatsApp($order, 'courier_assigned');
        }
    }

    /**
     * Kurye varış bildirimi gönder (Geofencing tarafından çağrılır)
     */
    public function sendArrivalNotification(Order $order): void
    {
        $settings = $this->getNotificationSettings($order->branch_id);

        // Varış bildirimi her zaman gönderilir (kritik bildirim)
        if ($settings['sms_enabled'] ?? false) {
            $this->sendSMS($order, 'courier_arrived');
        }

        if ($settings['whatsapp_enabled'] ?? false) {
            $this->sendWhatsApp($order, 'courier_arrived');
        }

        Log::info('Arrival notification sent', [
            'order_id' => $order->id,
            'customer_phone' => $order->customer_phone,
        ]);
    }

    /**
     * SMS gönder
     */
    public function sendSMS(Order $order, string $templateKey): bool
    {
        if (!$order->customer_phone) {
            return false;
        }

        $template = self::TEMPLATES[$templateKey] ?? null;
        if (!$template) {
            return false;
        }

        $message = $this->parseTemplate($template['sms'], $order);
        $phone = $this->formatPhoneNumber($order->customer_phone);

        try {
            // SMS API entegrasyonu (Netgsm, İletimerkezi, vb.)
            $success = $this->sendSmsViaProvider($phone, $message);

            // Log kaydet
            $this->logNotification($order, 'sms', $templateKey, $message, $success);

            return $success;
        } catch (\Exception $e) {
            Log::error('SMS gönderim hatası', [
                'order_id' => $order->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * WhatsApp gönder
     */
    public function sendWhatsApp(Order $order, string $templateKey): bool
    {
        if (!$order->customer_phone) {
            return false;
        }

        $template = self::TEMPLATES[$templateKey] ?? null;
        if (!$template) {
            return false;
        }

        $message = $this->parseTemplate($template['whatsapp'], $order);
        $phone = $this->formatPhoneNumber($order->customer_phone);

        try {
            // WhatsApp Business API entegrasyonu
            $success = $this->sendWhatsAppViaProvider($phone, $message);

            // Log kaydet
            $this->logNotification($order, 'whatsapp', $templateKey, $message, $success);

            return $success;
        } catch (\Exception $e) {
            Log::error('WhatsApp gönderim hatası', [
                'order_id' => $order->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Şablonu değişkenlerle doldur
     */
    private function parseTemplate(string $template, Order $order): string
    {
        $trackingUrl = $order->tracking_token
            ? route('tracking.show', $order->tracking_token)
            : '';

        $replacements = [
            '{customer_name}' => $order->customer_name,
            '{order_number}' => $order->order_number,
            '{tracking_code}' => $order->tracking_token,
            '{tracking_url}' => $trackingUrl,
            '{courier_name}' => $order->courier?->name ?? 'Kurye',
            '{eta}' => $order->getEstimatedMinutesRemaining() ?? '~20',
            '{cancel_reason}' => $order->cancel_reason ?? 'Belirtilmedi',
            '{support_phone}' => config('app.support_phone', ''),
            '{review_url}' => route('tracking.show', $order->tracking_token) . '?review=1',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Telefon numarasını formatla
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Türkiye formatı için
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 10) {
            $phone = '90' . $phone;
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = '9' . $phone;
        }

        return $phone;
    }

    /**
     * Status için şablon anahtarını al
     */
    private function getTemplateKeyForStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'order_confirmed',
            'preparing' => 'order_preparing',
            'ready' => 'order_ready',
            'on_delivery' => 'order_picked_up',
            'delivered' => 'order_delivered',
            'cancelled' => 'order_cancelled',
            default => null,
        };
    }

    /**
     * Bildirim ayarlarını al
     */
    private function getNotificationSettings(?int $branchId): array
    {
        // BranchSetting'den bildirim ayarlarını al
        $settings = \App\Models\BranchSetting::where('branch_id', $branchId)->first();

        return [
            'sms_enabled' => $settings->customer_sms_enabled ?? false,
            'whatsapp_enabled' => $settings->customer_whatsapp_enabled ?? false,
            'sms_provider' => config('services.sms.provider', 'netgsm'),
            'whatsapp_provider' => config('services.whatsapp.provider', 'twilio'),
            // Durum bazlı ayarlar
            'notify_on_confirmed' => $settings->notify_on_confirmed ?? true,
            'notify_on_preparing' => $settings->notify_on_preparing ?? false,
            'notify_on_ready' => $settings->notify_on_ready ?? false,
            'notify_on_courier_assigned' => $settings->notify_on_courier_assigned ?? true,
            'notify_on_picked_up' => $settings->notify_on_picked_up ?? true,
            'notify_on_delivered' => $settings->notify_on_delivered ?? true,
            'notify_on_cancelled' => $settings->notify_on_cancelled ?? true,
        ];
    }

    /**
     * SMS sağlayıcısı üzerinden gönder
     */
    private function sendSmsViaProvider(string $phone, string $message): bool
    {
        $provider = config('services.sms.provider', 'netgsm');

        return match ($provider) {
            'netgsm' => $this->sendViaNetgsm($phone, $message),
            'iletimerkezi' => $this->sendViaIletimerkezi($phone, $message),
            'twilio' => $this->sendViaTwilioSms($phone, $message),
            default => $this->logOnlySms($phone, $message),
        };
    }

    /**
     * Netgsm ile SMS gönder
     */
    private function sendViaNetgsm(string $phone, string $message): bool
    {
        $username = config('services.sms.netgsm.username');
        $password = config('services.sms.netgsm.password');
        $header = config('services.sms.netgsm.header');

        if (!$username || !$password) {
            Log::info('Netgsm yapılandırılmamış, SMS loglandı', ['phone' => $phone]);
            return $this->logOnlySms($phone, $message);
        }

        $response = Http::get('https://api.netgsm.com.tr/sms/send/get', [
            'usercode' => $username,
            'password' => $password,
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $header,
        ]);

        return str_starts_with($response->body(), '00');
    }

    /**
     * İletimerkezi ile SMS gönder
     */
    private function sendViaIletimerkezi(string $phone, string $message): bool
    {
        $apiKey = config('services.sms.iletimerkezi.api_key');
        $sender = config('services.sms.iletimerkezi.sender');

        if (!$apiKey) {
            return $this->logOnlySms($phone, $message);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.iletimerkezi.com/v1/send-sms', [
            'sender' => $sender,
            'to' => $phone,
            'message' => $message,
        ]);

        return $response->successful();
    }

    /**
     * Twilio ile SMS gönder
     */
    private function sendViaTwilioSms(string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token) {
            return $this->logOnlySms($phone, $message);
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => '+' . $phone,
                'Body' => $message,
            ]);

        return $response->successful();
    }

    /**
     * WhatsApp sağlayıcısı üzerinden gönder
     */
    private function sendWhatsAppViaProvider(string $phone, string $message): bool
    {
        $provider = config('services.whatsapp.provider', 'twilio');

        return match ($provider) {
            'twilio' => $this->sendViaTwilioWhatsApp($phone, $message),
            'wati' => $this->sendViaWati($phone, $message),
            default => $this->logOnlyWhatsApp($phone, $message),
        };
    }

    /**
     * Twilio WhatsApp ile gönder
     */
    private function sendViaTwilioWhatsApp(string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from', 'whatsapp:+14155238886');

        if (!$sid || !$token) {
            return $this->logOnlyWhatsApp($phone, $message);
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => 'whatsapp:+' . $phone,
                'Body' => $message,
            ]);

        return $response->successful();
    }

    /**
     * WATI ile WhatsApp gönder
     */
    private function sendViaWati(string $phone, string $message): bool
    {
        $apiKey = config('services.wati.api_key');
        $endpoint = config('services.wati.endpoint');

        if (!$apiKey || !$endpoint) {
            return $this->logOnlyWhatsApp($phone, $message);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post("{$endpoint}/api/v1/sendSessionMessage/{$phone}", [
            'messageText' => $message,
        ]);

        return $response->successful();
    }

    /**
     * Sadece loglama (test/geliştirme için)
     */
    private function logOnlySms(string $phone, string $message): bool
    {
        Log::info('SMS (Simülasyon)', [
            'phone' => $phone,
            'message' => $message,
        ]);
        return true;
    }

    private function logOnlyWhatsApp(string $phone, string $message): bool
    {
        Log::info('WhatsApp (Simülasyon)', [
            'phone' => $phone,
            'message' => $message,
        ]);
        return true;
    }

    /**
     * Bildirim logla
     */
    private function logNotification(Order $order, string $channel, string $template, string $message, bool $success): void
    {
        NotificationLog::create([
            'order_id' => $order->id,
            'channel' => $channel,
            'template' => $template,
            'message' => $message,
            'phone' => $order->customer_phone,
            'status' => $success ? 'sent' : 'failed',
            'sent_at' => now(),
        ]);
    }
}
