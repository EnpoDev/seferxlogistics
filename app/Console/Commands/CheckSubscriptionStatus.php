<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-subscription-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suresi dolmus abonelikleri kontrol edip expired durumuna getirir';

    /**
     * Execute the console command.
     *
     * MANTIK HATASI DUZELTILDI:
     * - Bu komut bos birakilmisti, abonelikler suresi dolsa bile aktif kaliyordu
     * - Simdi suresi dolan abonelikler expired olarak isaretleniyor
     */
    public function handle()
    {
        $this->info('Abonelik durumları kontrol ediliyor...');

        $expiredCount = 0;
        $trialExpiredCount = 0;
        $pastDueCount = 0;

        // 1. Suresi dolmus ACTIVE abonelikleri bul ve EXPIRED yap
        $expiredSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            $expiredCount++;

            Log::info('Subscription expired', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'ended_at' => $subscription->ends_at,
            ]);
        }

        // 2. Suresi dolmus TRIAL abonelikleri bul ve EXPIRED yap
        $expiredTrials = Subscription::where('status', Subscription::STATUS_TRIAL)
            ->where(function ($query) {
                $query->where(function ($q) {
                    // trial_ends_at gecmis
                    $q->whereNotNull('trial_ends_at')
                      ->where('trial_ends_at', '<', now());
                })->orWhere(function ($q) {
                    // veya ends_at gecmis
                    $q->whereNotNull('ends_at')
                      ->where('ends_at', '<', now());
                });
            })
            ->get();

        foreach ($expiredTrials as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            $trialExpiredCount++;

            Log::info('Trial subscription expired', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'trial_ended_at' => $subscription->trial_ends_at,
            ]);
        }

        // 3. Odeme tarihi gecmis abonelikleri PAST_DUE yap (next_billing_date kontrolu)
        $pastDueSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<', now()->subDays(3)) // 3 gun tolerans
            ->get();

        foreach ($pastDueSubscriptions as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_PAST_DUE]);
            $pastDueCount++;

            Log::warning('Subscription past due', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'next_billing_date' => $subscription->next_billing_date,
            ]);
        }

        // Sonuclari goster
        $this->info("İşlem tamamlandı:");
        $this->line("  - Süresi dolan abonelikler: {$expiredCount}");
        $this->line("  - Süresi dolan deneme abonelikleri: {$trialExpiredCount}");
        $this->line("  - Ödeme gecikmiş abonelikler: {$pastDueCount}");

        $total = $expiredCount + $trialExpiredCount + $pastDueCount;

        if ($total > 0) {
            Log::info('Subscription status check completed', [
                'expired' => $expiredCount,
                'trial_expired' => $trialExpiredCount,
                'past_due' => $pastDueCount,
            ]);
        }

        return Command::SUCCESS;
    }
}
