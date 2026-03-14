<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Order;
use App\Services\VoipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallController extends Controller
{
    protected VoipService $voipService;

    public function __construct(VoipService $voipService)
    {
        $this->voipService = $voipService;
    }

    /**
     * Kurye tarafından müşteriyi ara
     */
    public function callCustomer(Request $request, Order $order)
    {
        // Kurye yetkisi kontrolü
        $courier = Auth::guard('courier')->user();
        if (!$courier || $order->courier_id !== $courier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        $result = $this->voipService->initiateCall($order, CallLog::CALLER_COURIER);

        return response()->json($result);
    }

    /**
     * Müşteri tarafından kuryeyi ara
     */
    public function callCourier(Request $request, Order $order)
    {
        // Müşteri yetkisi kontrolü (portal session)
        $customer = session('portal_customer');
        if (!$customer || $order->customer_id !== $customer['id']) {
            return response()->json([
                'success' => false,
                'message' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        if (!$order->courier_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siparişe henüz kurye atanmamış.',
            ], 400);
        }

        $result = $this->voipService->initiateCall($order, CallLog::CALLER_CUSTOMER);

        return response()->json($result);
    }

    /**
     * Çağrı geçmişini getir
     */
    public function getLog(Order $order)
    {
        $callLogs = $this->voipService->getCallLog($order);

        return response()->json([
            'success' => true,
            'calls' => $callLogs->map(fn($log) => [
                'id' => $log->id,
                'caller_type' => $log->caller_type,
                'status' => $log->status,
                'status_label' => $log->getStatusLabel(),
                'duration' => $log->getDurationFormatted(),
                'started_at' => $log->started_at?->format('d.m.Y H:i'),
                'created_at' => $log->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * VOIP provider webhook
     */
    public function webhook(Request $request)
    {
        // Twilio signature validation
        if (!$this->validateVoipWebhook($request)) {
            \Log::warning('Invalid VOIP webhook request', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->all();

        $success = $this->voipService->handleWebhook($data);

        // Twilio TwiML yanıtı
        if ($request->header('Content-Type') === 'application/x-www-form-urlencoded') {
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'text/xml');
        }

        return response()->json(['success' => $success]);
    }

    /**
     * Çağrı bağlantı webhook'u (Twilio için)
     */
    public function connectWebhook(Request $request, int $callLogId)
    {
        // Twilio signature validation
        if (!$this->validateVoipWebhook($request)) {
            \Log::warning('Invalid VOIP connect webhook request', [
                'ip' => $request->ip(),
                'call_log_id' => $callLogId,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $callLog = CallLog::find($callLogId);

        if (!$callLog) {
            $response = new \Twilio\TwiML\VoiceResponse();
            $response->say('Çağrı bulunamadı', ['language' => 'tr-TR']);
            return response($response->asXML(), 200)->header('Content-Type', 'text/xml');
        }

        // SDK ile TwiML oluştur
        $twiml = $this->voipService->generateConnectTwiml($callLog);

        $callLog->update(['status' => CallLog::STATUS_RINGING]);

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * VOIP webhook dogrulama
     * Twilio X-Twilio-Signature header'i ile veya auth token ile dogrulama
     */
    private function validateVoipWebhook(Request $request): bool
    {
        $authToken = config('services.twilio.auth_token');

        // Auth token yoksa (VOIP yapilandirilmamis), istek reddedilir
        if (empty($authToken)) {
            return false;
        }

        // Twilio SDK ile imza dogrulama
        $signature = $request->header('X-Twilio-Signature');
        if ($signature) {
            $url = $request->fullUrl();
            $params = $request->all();
            $validator = new \Twilio\Security\RequestValidator($authToken);
            return $validator->validate($signature, $url, $params);
        }

        // Twilio signature yoksa, sadece bilinen IP adreslerinden izin ver
        // Twilio IP ranges: https://www.twilio.com/docs/sip-trunking/ip-addresses
        $trustedIps = config('services.voip.trusted_ips', []);
        if (!empty($trustedIps)) {
            return in_array($request->ip(), $trustedIps);
        }

        // Ne imza ne IP whitelist varsa - guvenli degil
        \Log::warning('VOIP webhook received without Twilio signature or IP whitelist', [
            'ip' => $request->ip(),
        ]);
        return false;
    }

    /**
     * Twilio yapılandırmasını doğrula (admin için)
     */
    public function verifyTwilio()
    {
        $result = $this->voipService->verifyCredentials();
        return response()->json($result);
    }

    /**
     * Kullanılabilir Twilio numaralarını listele (admin için)
     */
    public function listNumbers()
    {
        $numbers = $this->voipService->getAvailableNumbers();
        return response()->json([
            'success' => true,
            'numbers' => $numbers,
        ]);
    }
}
