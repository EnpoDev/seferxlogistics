<?php

use App\Models\Courier;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * User private channel - for user-specific notifications
 */
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/**
 * Branch private channel - for branch-specific updates
 */
Broadcast::channel('branch.{branchId}', function (User $user, int $branchId) {
    // User must belong to or own this branch
    return $user->branch_id === $branchId || $user->hasAccessToBranch($branchId);
});

/**
 * Courier presence channel - tracks online couriers per branch
 */
Broadcast::channel('couriers.{branchId}', function (User $user, int $branchId) {
    // Check if user has access to this branch
    if (!$user->hasAccessToBranch($branchId)) {
        return false;
    }

    // Return user data for presence channel
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role ?? 'user',
    ];
});

/**
 * Courier private channel - for courier-specific updates
 */
Broadcast::channel('courier.{courierId}', function (User $user, int $courierId) {
    // Check if user is this courier or has admin access
    $courier = Courier::find($courierId);

    if (!$courier) {
        return false;
    }

    // Courier can listen to their own channel
    if ($user->isCourier() && $user->courier_id === $courierId) {
        return true;
    }

    // Admins can listen to any courier channel
    return $user->isAdmin();
});

/**
 * Order private channel - for order-specific updates
 */
Broadcast::channel('order.{orderId}', function (User $user, int $orderId) {
    // Check if user has access to this order
    $order = \App\Models\Order::find($orderId);

    if (!$order) {
        return false;
    }

    // User owns the order, is assigned courier, or has admin access
    return $order->user_id === $user->id
        || $order->courier_id === $user->courier_id
        || $user->hasAccessToBranch($order->branch_id);
});

/**
 * Customer tracking channel - for public order tracking
 * Uses tracking token for authorization (no auth required)
 */
Broadcast::channel('tracking.{trackingToken}', function ($user, string $trackingToken) {
    // Allow anyone with the token to subscribe
    // This could be enhanced to verify the token exists
    return \App\Models\Order::where('tracking_token', $trackingToken)->exists();
});
