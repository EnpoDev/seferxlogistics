<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PaymentCard;
use App\Models\Subscription;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Show all plans
     */
    public function plans()
    {
        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentSubscription = auth()->user()->subscriptions()
            ->with('plan')
            ->valid()
            ->first();

        return view('pages.yonetim.paketler', compact('plans', 'currentSubscription'));
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $user = auth()->user();

        // Check if user has an active subscription
        $currentSubscription = $user->subscriptions()->valid()->first();
        
        if ($currentSubscription) {
            return back()->with('error', 'Zaten aktif bir aboneliğiniz var. Önce mevcut aboneliğinizi iptal edin.');
        }

        // Get default payment card
        $paymentCard = $user->paymentCards()->default()->first();

        try {
            DB::transaction(function () use ($user, $plan, $paymentCard) {
                // Create subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_card_id' => $paymentCard?->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
                    'next_billing_date' => $plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
                ]);

                // Create transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'payment_card_id' => $paymentCard?->id,
                    'type' => Transaction::TYPE_SUBSCRIPTION,
                    'amount' => $plan->price,
                    'currency' => Transaction::CURRENCY_TRY,
                    'status' => Transaction::STATUS_COMPLETED,
                    'description' => "{$plan->name} - {$plan->getPeriodLabel()} Abonelik",
                    'invoice_number' => Transaction::generateInvoiceNumber(),
                    'paid_at' => now(),
                ]);

                // Update subscription with payment info
                $subscription->update([
                    'last_payment_date' => now(),
                    'last_payment_amount' => $plan->price,
                ]);
            });

            return redirect()->route('yonetim.abonelikler')
                ->with('success', 'Aboneliğiniz başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            Log::error('Subscription creation failed: ' . $e->getMessage());
            return back()->with('error', 'Abonelik oluşturulurken bir hata oluştu.');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $subscription = auth()->user()->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Aktif abonelik bulunamadı.');
        }

        $subscription->cancel($request->input('reason'));

        return back()->with('success', 'Aboneliğiniz dönem sonunda iptal edilecektir.');
    }

    /**
     * Show payment cards
     */
    public function cards()
    {
        $cards = auth()->user()->paymentCards()->orderBy('is_default', 'desc')->get();

        return view('pages.yonetim.kartlar', compact('cards'));
    }

    /**
     * Store a new payment card
     */
    public function storeCard(Request $request)
    {
        $validated = $request->validate([
            'card_holder_name' => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'string', 'min:16', 'max:19'],
            'expiry_month' => ['required', 'integer', 'min:1', 'max:12'],
            'expiry_year' => ['required', 'integer', 'min:' . date('Y')],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
            'is_default' => ['boolean'],
        ]);

        $user = auth()->user();
        $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
        
        // If this is the first card or marked as default, set others to non-default
        if ($validated['is_default'] ?? false || $user->paymentCards()->count() === 0) {
            $user->paymentCards()->update(['is_default' => false]);
        }

        // Create card (in production, this would tokenize with payment gateway)
        $card = PaymentCard::create([
            'user_id' => $user->id,
            'card_holder_name' => strtoupper($validated['card_holder_name']),
            'card_number_last4' => substr($cardNumber, -4),
            'card_brand' => PaymentCard::detectBrand($cardNumber),
            'expiry_month' => $validated['expiry_month'],
            'expiry_year' => $validated['expiry_year'],
            'is_default' => $validated['is_default'] ?? ($user->paymentCards()->count() === 0),
            'gateway' => 'iyzico', // Default gateway
            'token' => 'tok_' . bin2hex(random_bytes(16)), // In production, get from gateway
        ]);

        return redirect()->route('yonetim.kartlar')
            ->with('success', 'Kart başarıyla eklendi.');
    }

    /**
     * Set card as default
     */
    public function setDefaultCard(PaymentCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }

        $card->setAsDefault();

        return back()->with('success', 'Varsayılan kart güncellendi.');
    }

    /**
     * Delete a payment card
     */
    public function destroyCard(PaymentCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if card is used by active subscription
        $activeSubscription = Subscription::where('payment_card_id', $card->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->first();

        if ($activeSubscription) {
            return back()->with('error', 'Bu kart aktif bir abonelikte kullanılıyor. Önce farklı bir kart belirleyin.');
        }

        $card->delete();

        return back()->with('success', 'Kart başarıyla silindi.');
    }

    /**
     * Show subscription details
     */
    public function subscription()
    {
        $subscription = auth()->user()->subscriptions()
            ->with(['plan', 'paymentCard'])
            ->latest()
            ->first();

        return view('pages.yonetim.abonelikler', compact('subscription'));
    }

    /**
     * Show transaction history
     */
    public function transactions(Request $request)
    {
        $query = auth()->user()->transactions()
            ->with(['subscription.plan', 'paymentCard'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $transactions = $query->paginate(20);

        return view('pages.yonetim.islemler', compact('transactions'));
    }

    /**
     * Download invoice
     */
    public function downloadInvoice(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();

        $pdf = Pdf::loadView('pdf.invoice', [
            'transaction' => $transaction->load(['subscription.plan', 'paymentCard']),
            'user' => $user,
        ]);

        $filename = 'fatura-' . $transaction->invoice_number . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request, Plan $plan)
    {
        $user = auth()->user();
        $currentSubscription = $user->subscriptions()->valid()->first();

        if (!$currentSubscription) {
            return redirect()->route('yonetim.paketler')
                ->with('error', 'Yükseltme yapılacak aktif abonelik bulunamadı.');
        }

        // Check if the new plan is actually higher
        if ($plan->price <= $currentSubscription->plan->price) {
            return back()->with('error', 'Seçilen plan mevcut planınızdan düşük veya eşit.');
        }

        try {
            DB::transaction(function () use ($user, $plan, $currentSubscription) {
                // Calculate prorated amount
                $daysRemaining = $currentSubscription->getDaysRemaining() ?? 0;
                $totalDays = $currentSubscription->plan->billing_period === 'yearly' ? 365 : 30;
                $unusedAmount = ($currentSubscription->plan->price / $totalDays) * $daysRemaining;
                $upgradeAmount = $plan->price - $unusedAmount;

                // Update subscription
                $currentSubscription->update([
                    'plan_id' => $plan->id,
                    'ends_at' => $plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
                    'next_billing_date' => $plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
                ]);

                // Create upgrade transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $currentSubscription->id,
                    'payment_card_id' => $currentSubscription->payment_card_id,
                    'type' => Transaction::TYPE_ONE_TIME,
                    'amount' => max(0, $upgradeAmount),
                    'currency' => Transaction::CURRENCY_TRY,
                    'status' => Transaction::STATUS_COMPLETED,
                    'description' => "Plan Yükseltme: {$plan->name}",
                    'invoice_number' => Transaction::generateInvoiceNumber(),
                    'paid_at' => now(),
                ]);
            });

            return redirect()->route('yonetim.abonelikler')
                ->with('success', 'Planınız başarıyla yükseltildi.');
        } catch (\Exception $e) {
            Log::error('Subscription upgrade failed: ' . $e->getMessage());
            return back()->with('error', 'Plan yükseltilirken bir hata oluştu.');
        }
    }
}

