<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
        ];
    }

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latest();
    }

    public function paymentCards(): HasMany
    {
        return $this->hasMany(PaymentCard::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if user has bayi role
     */
    public function hasBayi(): bool
    {
        return in_array('bayi', $this->roles ?? []);
    }

    /**
     * Check if user has isletme role
     */
    public function hasIsletme(): bool
    {
        return in_array('isletme', $this->roles ?? []);
    }

    /**
     * Check if user has multiple roles
     */
    public function hasMultipleRoles(): bool
    {
        return count($this->roles ?? []) > 1;
    }

    /**
     * Get user's first role
     */
    public function getFirstRole(): ?string
    {
        return $this->roles[0] ?? null;
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get user's current plan
     */
    public function getCurrentPlan(): ?Plan
    {
        $subscription = $this->subscriptions()
            ->with('plan')
            ->valid()
            ->first();

        return $subscription?->plan;
    }

    /**
     * Get default payment card
     */
    public function getDefaultCard(): ?PaymentCard
    {
        return $this->paymentCards()->where('is_default', true)->first();
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * Check if user is a courier
     */
    public function isCourier(): bool
    {
        return $this->hasRole('kurye');
    }

    /**
     * Check if user has access to a specific branch
     */
    public function hasAccessToBranch(int $branchId): bool
    {
        // Admins have access to all branches
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user's branch_id matches
        if ($this->branch_id === $branchId) {
            return true;
        }

        // Check if user has bayi role (branch owners)
        if ($this->hasBayi()) {
            // Check if user owns this branch
            return Branch::where('id', $branchId)
                ->where('user_id', $this->id)
                ->exists();
        }

        return false;
    }

    /**
     * Get the branch relationship (for işletme users)
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get branches owned by this user (for bayi users)
     */
    public function ownedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'user_id');
    }

    /**
     * Get the main/parent branch owned by this user
     */
    public function getMainBranch(): ?Branch
    {
        return $this->ownedBranches()
            ->whereNull('parent_id')
            ->first();
    }

    /**
     * Get all child branches (işletmeler) under this user's main branch
     */
    public function getChildBranches()
    {
        $mainBranch = $this->getMainBranch();
        if (!$mainBranch) {
            return collect();
        }

        return Branch::where('parent_id', $mainBranch->id)->get();
    }

    /**
     * Get the effective subscription for this user
     * For bayi users: returns their own subscription
     * For işletme users: returns the parent bayi's subscription
     */
    public function getEffectiveSubscription(): ?Subscription
    {
        // If user is a bayi, return their own subscription
        if ($this->hasBayi()) {
            return $this->subscriptions()
                ->with('plan')
                ->valid()
                ->first();
        }

        // If user is an işletme, get the parent bayi's subscription
        if ($this->hasIsletme() && $this->branch_id) {
            $branch = Branch::find($this->branch_id);
            if ($branch) {
                return $branch->getOwnerSubscription();
            }
        }

        return null;
    }

    /**
     * Get the effective plan for this user (considering parent bayi for işletmeler)
     */
    public function getEffectivePlan(): ?Plan
    {
        $subscription = $this->getEffectiveSubscription();
        return $subscription?->plan;
    }

    /**
     * Check if user can use a specific feature (considering parent bayi for işletmeler)
     */
    public function canUseFeature(string $feature): bool
    {
        $plan = $this->getEffectivePlan();
        return $plan ? $plan->hasFeature($feature) : false;
    }

    /**
     * Check if user has an effective active subscription (considering parent bayi for işletmeler)
     */
    public function hasEffectiveSubscription(): bool
    {
        return $this->getEffectiveSubscription() !== null;
    }

    /**
     * Get the courier relationship
     */
    public function courier()
    {
        return $this->hasOne(Courier::class);
    }

    /**
     * Get the restaurant connections (external platforms like seferxyemek)
     */
    public function restaurantConnections(): HasMany
    {
        return $this->hasMany(RestaurantConnection::class);
    }

    /**
     * Get active restaurant connections
     */
    public function activeRestaurantConnections(): HasMany
    {
        return $this->hasMany(RestaurantConnection::class)->where('is_active', true);
    }
}
