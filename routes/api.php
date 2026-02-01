<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExternalOrderController;

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
