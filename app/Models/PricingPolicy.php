<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPolicy extends Model
{
    protected $fillable = [
        'branch_id',
        'type',
        'policy_type',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Default pricing values
    public const DEFAULT_COURIER_PERCENTAGE = 0.60; // 60%
    public const DEFAULT_BRANCH_PERCENTAGE = 0.40; // 40%
    public const DEFAULT_KM_RATE_BUSINESS = 2.0; // 2 TL/km
    public const DEFAULT_KM_RATE_COURIER = 1.2; // 1.2 TL/km
    public const DEFAULT_BASE_FEE = 10.0; // 10 TL base

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PricingPolicyRule::class);
    }

    public function couriers(): HasMany
    {
        return $this->hasMany(Courier::class);
    }

    /**
     * Get pricing configuration for a specific courier
     */
    public static function getPricingForCourier(?int $courierId): array
    {
        if (!$courierId) {
            return self::getDefaultPricing();
        }

        $courier = Courier::find($courierId);

        if (!$courier) {
            return self::getDefaultPricing();
        }

        // Check if courier has custom pricing_data
        if (!empty($courier->pricing_data)) {
            return self::parseCourierPricingData($courier);
        }

        // Check if courier has a pricing policy
        if ($courier->pricing_policy_id) {
            $policy = self::with('rules')->find($courier->pricing_policy_id);
            if ($policy) {
                return self::parsePolicyRules($policy, $courier);
            }
        }

        return self::getDefaultPricing();
    }

    /**
     * Get pricing configuration for a specific branch
     */
    public static function getPricingForBranch(?int $branchId): array
    {
        if (!$branchId) {
            return self::getDefaultPricing();
        }

        $policy = self::where('branch_id', $branchId)
            ->where('is_active', true)
            ->with('rules')
            ->first();

        if (!$policy) {
            return self::getDefaultPricing();
        }

        return self::parsePolicyRules($policy);
    }

    /**
     * Get default pricing values
     */
    public static function getDefaultPricing(): array
    {
        return [
            'courier_percentage' => self::DEFAULT_COURIER_PERCENTAGE,
            'branch_percentage' => self::DEFAULT_BRANCH_PERCENTAGE,
            'km_rate_business' => self::DEFAULT_KM_RATE_BUSINESS,
            'km_rate_courier' => self::DEFAULT_KM_RATE_COURIER,
            'base_fee' => self::DEFAULT_BASE_FEE,
            'policy_name' => 'Standart Politika',
        ];
    }

    /**
     * Calculate courier earnings for an order
     */
    public static function calculateCourierEarnings(Order $order): float
    {
        $pricing = self::getPricingForCourier($order->courier_id);

        // Working type based calculation
        $courier = $order->courier;
        if ($courier && $courier->working_type) {
            return self::calculateByWorkingType($order, $courier, $pricing);
        }

        // Default: percentage of delivery fee
        return $order->delivery_fee * $pricing['courier_percentage'];
    }

    /**
     * Calculate branch earnings for an order
     */
    public static function calculateBranchEarnings(Order $order): float
    {
        $pricing = self::getPricingForBranch($order->branch_id);

        return $order->delivery_fee * $pricing['branch_percentage'];
    }

    /**
     * Calculate KM-based earnings
     */
    public static function calculateKmEarnings(Order $order, string $type = 'business'): float
    {
        $distance = $order->delivery_distance ?? 0;

        if ($type === 'courier') {
            $pricing = self::getPricingForCourier($order->courier_id);
            return $distance * $pricing['km_rate_courier'];
        }

        $pricing = self::getPricingForBranch($order->branch_id);
        return $distance * $pricing['km_rate_business'];
    }

    /**
     * Parse courier's custom pricing_data
     */
    protected static function parseCourierPricingData(Courier $courier): array
    {
        $data = $courier->pricing_data;
        $defaults = self::getDefaultPricing();

        return [
            'courier_percentage' => $data['courier_percentage'] ?? $defaults['courier_percentage'],
            'branch_percentage' => $data['branch_percentage'] ?? $defaults['branch_percentage'],
            'km_rate_business' => $data['km_rate_business'] ?? $defaults['km_rate_business'],
            'km_rate_courier' => $data['km_rate_courier'] ?? $defaults['km_rate_courier'],
            'base_fee' => $data['base_fee'] ?? $defaults['base_fee'],
            'per_package_fee' => $data['per_package_fee'] ?? 0,
            'per_km_fee' => $data['per_km_fee'] ?? 0,
            'min_km' => $data['min_km'] ?? 0,
            'max_km' => $data['max_km'] ?? 0,
            'fixed_km' => $data['fixed_km'] ?? 0,
            'fixed_km_fee' => $data['fixed_km_fee'] ?? 0,
            'commission_rate' => $data['commission_rate'] ?? 0,
            'policy_name' => 'Özel Fiyatlandırma',
        ];
    }

    /**
     * Parse policy rules to pricing array
     */
    protected static function parsePolicyRules(self $policy, ?Courier $courier = null): array
    {
        $defaults = self::getDefaultPricing();
        $result = [
            'policy_name' => $policy->name,
            'courier_percentage' => $defaults['courier_percentage'],
            'branch_percentage' => $defaults['branch_percentage'],
            'km_rate_business' => $defaults['km_rate_business'],
            'km_rate_courier' => $defaults['km_rate_courier'],
            'base_fee' => $defaults['base_fee'],
        ];

        foreach ($policy->rules as $rule) {
            // Rules can define different rates based on min_value/max_value ranges
            if ($rule->percentage > 0) {
                // Percentage-based rule
                $result['courier_percentage'] = $rule->percentage / 100;
                $result['branch_percentage'] = 1 - $result['courier_percentage'];
            }

            if ($rule->price > 0) {
                // Fixed price per unit
                $result['km_rate_courier'] = (float) $rule->price;
            }
        }

        return $result;
    }

    /**
     * Calculate earnings based on courier's working type
     */
    protected static function calculateByWorkingType(Order $order, Courier $courier, array $pricing): float
    {
        $distance = $order->delivery_distance ?? 0;
        $deliveryFee = $order->delivery_fee ?? 0;

        return match ($courier->working_type) {
            'per_package' => $pricing['per_package_fee'] ?? $deliveryFee * $pricing['courier_percentage'],

            'per_km' => $distance * ($pricing['per_km_fee'] ?? $pricing['km_rate_courier']),

            'km_range' => self::calculateKmRange($distance, $pricing),

            'package_plus_km' => ($pricing['per_package_fee'] ?? 0)
                + ($distance * ($pricing['per_km_fee'] ?? $pricing['km_rate_courier'])),

            'fixed_km_plus_km' => self::calculateFixedKmPlusKm($distance, $pricing),

            'commission' => $deliveryFee * (($pricing['commission_rate'] ?? 60) / 100),

            'tiered_package' => self::calculateTieredPackage($order, $courier),

            default => $deliveryFee * $pricing['courier_percentage'],
        };
    }

    /**
     * Calculate KM range pricing
     */
    protected static function calculateKmRange(float $distance, array $pricing): float
    {
        $minKm = $pricing['min_km'] ?? 0;
        $maxKm = $pricing['max_km'] ?? 999;
        $rate = $pricing['per_km_fee'] ?? self::DEFAULT_KM_RATE_COURIER;

        if ($distance >= $minKm && $distance <= $maxKm) {
            return $distance * $rate;
        }

        return 0;
    }

    /**
     * Calculate fixed KM + extra KM pricing
     */
    protected static function calculateFixedKmPlusKm(float $distance, array $pricing): float
    {
        $fixedKm = $pricing['fixed_km'] ?? 3;
        $fixedFee = $pricing['fixed_km_fee'] ?? 15;
        $extraRate = $pricing['per_km_fee'] ?? self::DEFAULT_KM_RATE_COURIER;

        $extraKm = max(0, $distance - $fixedKm);

        return $fixedFee + ($extraKm * $extraRate);
    }

    /**
     * Calculate tiered package pricing
     */
    protected static function calculateTieredPackage(Order $order, Courier $courier): float
    {
        // Get today's delivery count for this courier
        $todayDeliveries = Order::where('courier_id', $courier->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('delivered_at', today())
            ->count();

        $pricingData = $courier->pricing_data ?? [];
        $tiers = $pricingData['tiers'] ?? [
            ['min' => 0, 'max' => 10, 'fee' => 8],
            ['min' => 11, 'max' => 20, 'fee' => 10],
            ['min' => 21, 'max' => 999, 'fee' => 12],
        ];

        foreach ($tiers as $tier) {
            if ($todayDeliveries >= $tier['min'] && $todayDeliveries <= $tier['max']) {
                return $tier['fee'];
            }
        }

        return self::DEFAULT_BASE_FEE;
    }
}
