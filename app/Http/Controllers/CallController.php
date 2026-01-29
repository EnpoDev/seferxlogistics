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
