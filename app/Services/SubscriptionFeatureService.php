<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionFeatureService
{
    /**
     * Check if a user can use a specific feature
     * Considers parent bayi's subscription for işletme users
     */
    public function userCanUseFeature(User $user, string $feature): bool
    {
        return $user->canUseFeature($feature);
    }

    /**
     * Check if a branch can use a specific feature
     * Considers parent bayi's subscription for child branches
     */
    public function branchCanUseFeature(Branch $branch, string $feature): bool
    {
        return $branch->canUseFeature($feature);
    }

    /**
     * Get the effective plan for a user
     */
    public function getUserPlan(User $user): ?Plan
    {
        return $user->getEffectivePlan();
    }

    /**
     * Get the effective plan for a branch
     */
    public function getBranchPlan(Branch $branch): ?Plan
    {
        return $branch->getCurrentPlan();
    }

    /**
     * Get the effective subscription for a user
     */
    public function getUserSubscription(User $user): ?Subscription
    {
        return $user->getEffectiveSubscription();
    }

    /**
     * Get the effective subscription for a branch
     */
    public function getBranchSubscription(Branch $branch): ?Subscription
    {
        return $branch->getOwnerSubscription();
    }

    /**
     * Check if user has any active subscription
     */
    public function userHasSubscription(User $user): bool
    {
        return $user->hasEffectiveSubscription();
    }

    /**
     * Check if branch has any active subscription
     */
    public function branchHasSubscription(Branch $branch): bool
    {
        return $branch->hasActiveSubscription();
    }

    /**
     * Get all features available for a user
     */
    public function getUserFeatures(User $user): array
    {
        $plan = $user->getEffectivePlan();
        return $plan?->features ?? [];
    }

    /**
     * Get all features available for a branch
     */
    public function getBranchFeatures(Branch $branch): array
    {
        $plan = $branch->getCurrentPlan();
        return $plan?->features ?? [];
    }

    /**
     * Get plan limits for a user
     */
    public function getUserPlanLimits(User $user): array
    {
        $plan = $user->getEffectivePlan();

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

    /**
     * Check if user can add more branches
     */
    public function userCanAddBranch(User $user): bool
    {
        $mainBranch = $user->getMainBranch();
        if (!$mainBranch) {
            return true; // No branch yet, can create first one
        }

        return $mainBranch->canAddMoreBranches();
    }

    /**
     * Get subscription status info for display
     */
    public function getSubscriptionStatusInfo(User $user): array
    {
        $subscription = $user->getEffectiveSubscription();

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
                'status_label' => __('messages.subscription.no_subscription'),
                'plan_name' => null,
                'days_remaining' => null,
                'is_inherited' => false,
            ];
        }

        $isInherited = $user->hasIsletme() && !$user->hasBayi();

        return [
            'has_subscription' => true,
            'status' => $subscription->status,
            'status_label' => $subscription->getStatusLabel(),
            'plan_name' => $subscription->plan?->name,
            'days_remaining' => $subscription->getDaysRemaining(),
            'is_inherited' => $isInherited,
            'owner_name' => $isInherited ? $subscription->user?->name : null,
        ];
    }
}
