<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class VoipService
{
    protected string $provider;
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $proxyNumber;
    protected ?Client $twilioClient = null;

    public function __construct()
    {
        $this->provider = config('services.voip.provider', 'direct');
        $this->accountSid = config('services.voip.account_sid');
        $this->authToken = config('services.voip.auth_token');
        $this->proxyNumber = config('services.voip.proxy_number');

        // Initialize Twilio client if credentials are available
        if ($this->provider === 'twilio' && $this->accountSid && $this->authToken) {
            try {
                $this->twilioClient = new Client($this->accountSid, $this->authToken);
            } catch (\Exception $e) {
                Log::error('Failed to initialize Twilio client', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Çağrı başlat
     */
    public function initiateCall(Order $order, string $callerType): array
    {
        // Arayan ve aranan numaraları belirle
        if ($callerType === CallLog::CALLER_CUSTOMER) {
            $fromNumber = $order->customer_phone;
            $toNumber = $order->courier?->phone;
        } else {
            $fromNumber = $order->courier?->phone;
            $toNumber = $order->customer_phone;
        }

        if (!$toNumber) {
            return [
                'success' => false,
                'message' => 'Hedef telefon numarası bulunamadı',
            ];
        }

        // Çağrı kaydı oluştur
        $callLog = CallLog::create([
            'order_id' => $order->id,
            'caller_type' => $callerType,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'proxy_number' => $this->proxyNumber,
            'status' => CallLog::STATUS_INITIATED,
        ]);

        // VOIP provider'a bağlı olarak çağrıyı başlat
        try {
            $result = match ($this->provider) {
                'twilio' => $this->initiateTwilioCall($callLog),
                'telnyx' => $this->initiateTelnyxCall($callLog),
                default => $this->initiateDirectCall($callLog),
            };

            return $result;
        } catch (\Exception $e) {
            Log::error('VOIP call initiation failed', [
                'call_log_id' => $callLog->id,
                'error' => $e->getMessage(),
            ]);

            $callLog->update(['status' => CallLog::STATUS_FAILED]);

            return [
                'success' => false,
                'message' => 'Çağrı başlatılamadı: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Twilio ile çağrı başlat (SDK kullanarak)
     */
    protected function initiateTwilioCall(CallLog $callLog): array
    {
        if (!$this->twilioClient) {
            // Twilio yapılandırılmamış, direkt arama yönlendir
            return $this->initiateDirectCall($callLog);
        }

        try {
            // Twilio SDK ile çağrı oluştur
            $call = $this->twilioClient->calls->create(
                $callLog->to_number, // To
                $this->proxyNumber,   // From (Twilio number)
                [
                    'url' => route('voip.webhook.connect', ['callLogId' => $callLog->id]),
                    'statusCallback' => route('voip.webhook'),
                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                    'statusCallbackMethod' => 'POST',
                ]
            );

            $callLog->update([
                'external_call_id' => $call->sid,
                'status' => CallLog::STATUS_RINGING,
                'metadata' => [
                    'twilio_sid' => $call->sid,
                    'twilio_status' => $call->status,
                ],
            ]);

            return [
                'success' => true,
                'call_log_id' => $callLog->id,
                'call_sid' => $call->sid,
                'message' => 'Çağrı başlatıldı',
            ];
        } catch (\Twilio\Exceptions\TwilioException $e) {
            Log::error('Twilio call failed', [
                'call_log_id' => $callLog->id,
                'error_code' => $e->getCode(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Twilio çağrısı başlatılamadı: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Telnyx ile çağrı başlat
     */
    protected function initiateTelnyxCall(CallLog $callLog): array
    {
        // Telnyx API entegrasyonu - placeholder
        return $this->initiateDirectCall($callLog);
    }

    /**
     * Direkt arama (tel: link)
     */
    protected function initiateDirectCall(CallLog $callLog): array
    {
        $callLog->update([
            'status' => CallLog::STATUS_INITIATED,
            'started_at' => now(),
        ]);

        return [
            'success' => true,
            'call_log_id' => $callLog->id,
            'direct_call' => true,
            'phone_number' => $callLog->to_number,
            'tel_link' => 'tel:' . $callLog->to_number,
            'message' => 'Arama için yönlendiriliyorsunuz',
        ];
    }

    /**
     * Maskeli numara al
     */
    public function getMaskedNumber(Order $order, string $viewerType): string
    {
        if ($viewerType === 'customer') {
            // Müşteri kurye numarasını görmemeli
            return $this->proxyNumber ?? $this->maskPhoneNumber($order->courier?->phone ?? '');
        } else {
            // Kurye müşteri numarasını görebilir (veya proxy)
            return $this->proxyNumber ?? $order->customer_phone;
        }
    }

    /**
     * Telefon numarasını maskele
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }

        // Son 4 haneyi göster, kalanını maskele
        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }

    /**
     * Çağrıyı sonlandır
     */
    public function endCall(CallLog $callLog): bool
    {
        // Twilio'da aktif çağrıyı sonlandır
        if ($this->twilioClient && $callLog->external_call_id) {
            try {
                $this->twilioClient->calls($callLog->external_call_id)
                    ->update(['status' => 'completed']);
            } catch (\Exception $e) {
                Log::warning('Failed to end Twilio call', [
                    'call_log_id' => $callLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $callLog->update([
            'status' => CallLog::STATUS_COMPLETED,
            'ended_at' => now(),
            'duration' => $callLog->started_at
                ? $callLog->started_at->diffInSeconds(now())
                : null,
        ]);

        return true;
    }

    /**
     * Çağrı geçmişini getir
     */
    public function getCallLog(Order $order): \Illuminate\Database\Eloquent\Collection
    {
        return CallLog::where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Webhook - Çağrı durumu güncelleme
     */
    public function handleWebhook(array $data): bool
    {
        $callSid = $data['CallSid'] ?? $data['call_control_id'] ?? null;
        $status = $data['CallStatus'] ?? $data['state'] ?? null;

        if (!$callSid) {
            return false;
        }

        $callLog = CallLog::where('external_call_id', $callSid)->first();

        if (!$callLog) {
            return false;
        }

        $newStatus = match (strtolower($status)) {
            'queued', 'initiated' => CallLog::STATUS_INITIATED,
            'ringing' => CallLog::STATUS_RINGING,
            'in-progress', 'answered' => CallLog::STATUS_ANSWERED,
            'completed' => CallLog::STATUS_COMPLETED,
            'no-answer' => CallLog::STATUS_MISSED,
            'busy' => CallLog::STATUS_BUSY,
            'failed', 'canceled' => CallLog::STATUS_FAILED,
            default => $callLog->status,
        };

        $updates = [
            'status' => $newStatus,
            'metadata' => array_merge($callLog->metadata ?? [], [
                'last_webhook' => now()->toISOString(),
                'webhook_status' => $status,
                'call_duration' => $data['CallDuration'] ?? null,
            ]),
        ];

        if ($newStatus === CallLog::STATUS_ANSWERED && !$callLog->started_at) {
            $updates['started_at'] = now();
        }

        if (in_array($newStatus, [CallLog::STATUS_COMPLETED, CallLog::STATUS_MISSED, CallLog::STATUS_FAILED, CallLog::STATUS_BUSY])) {
            $updates['ended_at'] = now();

            // Use Twilio's duration if available
            if (isset($data['CallDuration'])) {
                $updates['duration'] = (int) $data['CallDuration'];
            } elseif ($callLog->started_at) {
                $updates['duration'] = $callLog->started_at->diffInSeconds(now());
            }
        }

        $callLog->update($updates);

        return true;
    }

    /**
     * TwiML yanıtı oluştur - Çağrı bağlantısı için
     */
    public function generateConnectTwiml(CallLog $callLog): string
    {
        $response = new VoiceResponse();

        // Karşılama mesajı
        $response->say(
            'Çağrınız yönlendiriliyor, lütfen bekleyiniz.',
            ['language' => 'tr-TR']
        );

        // Çağrıyı yönlendir
        $dial = $response->dial('', [
            'callerId' => $this->proxyNumber ?? $callLog->from_number,
            'timeout' => 30,
            'action' => route('voip.webhook'),
        ]);

        $dial->number($callLog->to_number);

        return $response->asXML();
    }

    /**
     * Twilio hesap bilgilerini doğrula
     */
    public function verifyCredentials(): array
    {
        if (!$this->twilioClient) {
            return [
                'success' => false,
                'message' => 'Twilio yapılandırılmamış',
            ];
        }

        try {
            $account = $this->twilioClient->api->v2010->accounts($this->accountSid)->fetch();

            return [
                'success' => true,
                'account_name' => $account->friendlyName,
                'account_status' => $account->status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Twilio doğrulama hatası: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kullanılabilir Twilio telefon numaralarını listele
     */
    public function getAvailableNumbers(): array
    {
        if (!$this->twilioClient) {
            return [];
        }

        try {
            $numbers = $this->twilioClient->incomingPhoneNumbers->read([], 20);

            return array_map(fn($n) => [
                'phone_number' => $n->phoneNumber,
                'friendly_name' => $n->friendlyName,
                'capabilities' => $n->capabilities,
            ], $numbers);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Twilio numbers', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
