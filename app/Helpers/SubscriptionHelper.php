<?php

namespace App\Helpers;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionHelper
{
    /**
     * Check if the current authenticated user can use a feature
     */
    public static function canUseFeature(string $feature): bool
    {
        $user = auth()->user();
        return $user && $user->canUseFeature($feature);
    }

    /**
     * Check if the current authenticated user has any active subscription
     */
    public static function hasSubscription(): bool
    {
        $user = auth()->user();
        return $user && $user->hasEffectiveSubscription();
    }

    /**
     * Get the current authenticated user's effective plan
     */
    public static function getCurrentPlan(): ?Plan
    {
        $user = auth()->user();
        return $user?->getEffectivePlan();
    }

    /**
     * Get the current authenticated user's effective subscription
     */
    public static function getCurrentSubscription(): ?Subscription
    {
        $user = auth()->user();
        return $user?->getEffectiveSubscription();
    }

    /**
     * Get all features available to the current authenticated user
     */
    public static function getFeatures(): array
    {
        $plan = static::getCurrentPlan();
        return $plan?->features ?? [];
    }

    /**
     * Check if a specific user can use a feature
     */
    public static function userCanUseFeature(User $user, string $feature): bool
    {
        return $user->canUseFeature($feature);
    }

    /**
     * Check if a specific branch can use a feature
     */
    public static function branchCanUseFeature(Branch $branch, string $feature): bool
    {
        return $branch->canUseFeature($feature);
    }

    /**
     * Get plan limits for the current authenticated user
     */
    public static function getPlanLimits(): array
    {
        $plan = static::getCurrentPlan();

        if (!$plan) {
            return [
                'max_users' => 1,
                'max_orders' => 100,
                'max_branches' => 1,
            ];
        }

        return [
            'max_users' => $plan->max_users,
            'max_orders' => $plan->max_orders,
            'max_branches' => $plan->max_branches,
        ];
    }
}
