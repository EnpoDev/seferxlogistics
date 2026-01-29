<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'lat',
        'lng',
        'is_main',
        'is_active',
        'parent_id',
        'user_id',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_main' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function parent()
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    public function settings()
    {
        return $this->hasOne(BranchSetting::class);
    }

    public function pricingPolicies()
    {
        return $this->hasMany(PricingPolicy::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class)
            ->withTimestamps();
    }

    /**
     * Get the owner (bayi) of this branch
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the root/main branch in the hierarchy
     * For child branches, returns the parent branch
     * For parent branches, returns itself
     */
    public function getRootBranch(): ?Branch
    {
        if ($this->parent_id === null) {
            return $this;
        }

        return $this->parent;
    }

    /**
     * Get the owner user of this branch hierarchy
     * For child branches (işletmeler), returns the parent branch's owner (bayi)
     */
    public function getOwnerUser(): ?User
    {
        // If this branch has an owner, return it
        if ($this->user_id) {
            return $this->owner;
        }

        // If this is a child branch, get the parent's owner
        $rootBranch = $this->getRootBranch();
        if ($rootBranch && $rootBranch->user_id) {
            return $rootBranch->owner;
        }

        return null;
    }

    /**
     * Get the active subscription for this branch's owner
     */
    public function getOwnerSubscription(): ?Subscription
    {
        $owner = $this->getOwnerUser();
        if (!$owner) {
            return null;
        }

        return $owner->subscriptions()
            ->with('plan')
            ->valid()
            ->first();
    }

    /**
     * Check if this branch's owner has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        $owner = $this->getOwnerUser();
        return $owner ? $owner->hasActiveSubscription() : false;
    }

    /**
     * Get the current plan for this branch's owner
     */
    public function getCurrentPlan(): ?Plan
    {
        $owner = $this->getOwnerUser();
        return $owner?->getCurrentPlan();
    }

    /**
     * Check if this branch can use a specific feature
     */
    public function canUseFeature(string $feature): bool
    {
        $plan = $this->getCurrentPlan();
        return $plan ? $plan->hasFeature($feature) : false;
    }

    /**
     * Get the maximum allowed child branches based on subscription
     */
    public function getMaxBranches(): int
    {
        $plan = $this->getCurrentPlan();
        return $plan?->max_branches ?? 1;
    }

    /**
     * Check if the owner can add more branches
     */
    public function canAddMoreBranches(): bool
    {
        $rootBranch = $this->getRootBranch();
        if (!$rootBranch) {
            return false;
        }

        $currentCount = Branch::where('parent_id', $rootBranch->id)->count();
        $maxBranches = $this->getMaxBranches();

        return $currentCount < $maxBranches;
    }
}
