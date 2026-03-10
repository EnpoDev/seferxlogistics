<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExternalOrderController;
use App\Http\Controllers\Api\CallerIdController;

/*
|--------------------------------------------------------------------------
| External API Routes (OAuth2 Protected)
|--------------------------------------------------------------------------
|
| These routes are used by external platforms (like seferxyemek) to
| interact with the logistics system. All routes require valid OAuth2
| access token authentication.
|
*/

Route::prefix('external')->middleware(['auth:api', 'throttle:60,1'])->group(function () {
    // Order Management
    Route::post('/orders', [ExternalOrderController::class, 'store']);
    Route::get('/orders/{externalOrderId}', [ExternalOrderController::class, 'show']);
    Route::patch('/orders/{externalOrderId}/status', [ExternalOrderController::class, 'updateStatus']);
    Route::post('/orders/{externalOrderId}/cancel', [ExternalOrderController::class, 'cancel']);

    // Restaurant Connections
    Route::get('/restaurants', [ExternalOrderController::class, 'restaurants']);
    Route::patch('/restaurants/{connectionId}/settings', [ExternalOrderController::class, 'updateRestaurantSettings']);

    // Webhook Secret Management
    Route::get('/restaurants/{connectionId}/webhook-secret', [ExternalOrderController::class, 'getWebhookSecret']);
    Route::post('/restaurants/{connectionId}/webhook-secret/regenerate', [ExternalOrderController::class, 'regenerateWebhookSecret']);
});

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('public')->middleware(['throttle:120,1'])->group(function () {
    // Order tracking (no auth required, uses tracking token)
    Route::get('/track/{trackingToken}', function (string $trackingToken) {
        $order = \App\Models\Order::where('tracking_token', $trackingToken)
            ->with(['courier:id,name', 'items'])
            ->first();

        if (!$order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'courier' => $order->courier ? [
                'name' => $order->courier->name,
            ] : null,
            'estimated_delivery' => $order->estimated_delivery_at,
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Caller ID API Routes (API Key Protected)
|--------------------------------------------------------------------------
|
| These routes are used by the SeferX Caller ID iOS app to lookup
| customer information when receiving phone calls.
|
*/

Route::prefix('caller-id')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    // Lookup customer by phone number
    Route::get('/lookup', [CallerIdController::class, 'lookup']);

    // Sync customers for offline storage
    Route::get('/sync', [CallerIdController::class, 'sync']);
});

/*
|--------------------------------------------------------------------------
| Caller ID Device Routes (Public - No Auth)
|--------------------------------------------------------------------------
|
| These routes are used by physical Caller ID devices to send incoming
| call information to the system. No authentication required as the
| device cannot store API keys.
|
| URL format: /api/cagri/al/{branchId}?no={phoneNumber}
| Optional params: &DeviceID=xxx&DateTime=xxx&Line=x&str0=xxx&str1=xxx
|
*/

Route::prefix('cagri')->middleware(['throttle:120,1'])->group(function () {
    // Receive incoming call from Caller ID device
    Route::get('/al/{branchId}', [CallerIdController::class, 'receive']);
});

/*
|--------------------------------------------------------------------------
| Authenticated API Routes
|--------------------------------------------------------------------------
| Note: /isletmem/recent-calls moved to routes/web.php for proper
| session-based authentication handling.
|--------------------------------------------------------------------------
*/
