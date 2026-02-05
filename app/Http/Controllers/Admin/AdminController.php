<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Integration;
use App\Models\Transaction;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    // ===================================
    // DASHBOARD
    // ===================================
    public function dashboard()
    {
        $stats = [
            'total_bayiler' => User::whereJsonContains('roles', 'bayi')->count(),
            'total_kuryeler' => Courier::count(),
            'total_subeler' => Branch::count(),
            'total_siparisler' => Order::count(),
            'bugun_siparisler' => Order::whereDate('created_at', today())->count(),
            'haftalik_siparisler' => Order::where('created_at', '>=', now()->subWeek())->count(),
            'aylik_siparisler' => Order::where('created_at', '>=', now()->subMonth())->count(),
            'aktif_abonelikler' => Subscription::where('status', 'active')->count(),
            'aktif_kuryeler' => Courier::where('status', 'available')->count(),
            'bekleyen_destek' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $son_siparisler = Order::with(['branch', 'courier'])
            ->latest()
            ->take(10)
            ->get();

        $son_bayiler = User::whereJsonContains('roles', 'bayi')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'son_siparisler', 'son_bayiler'));
    }

    // ===================================
    // BAYILER
    // ===================================
    public function bayiler(Request $request)
    {
        $query = User::whereJsonContains('roles', 'bayi')
            ->with('activeSubscription.plan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->whereHas('activeSubscription', fn($q) => $q->where('status', 'active'));
            } elseif ($request->status === 'pasif') {
                $query->whereDoesntHave('activeSubscription', fn($q) => $q->where('status', 'active'));
            }
        }

        $bayiler = $query->orderBy('created_at', 'desc')->paginate(20);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.bayiler.index', compact('bayiler', 'plans'));
    }

    public function bayiCreate()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('admin.bayiler.create', compact('plans'));
    }

    public function bayiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:20'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'roles' => ['bayi'],
            'email_verified_at' => now(),
        ]);

        // Varsayilan plan ata
        $planId = $validated['plan_id'] ?? Plan::where('is_active', true)->orderBy('sort_order')->first()?->id;

        if ($planId) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => 'active',
                'starts_at' => now(),
                'next_billing_date' => now()->addMonth(),
            ]);
        }

        return redirect()
            ->route('admin.bayiler.index')
            ->with('success', 'Bayi basariyla olusturuldu.');
    }

    public function bayiShow(User $user)
    {
        $user->load(['activeSubscription.plan', 'subscriptions.plan']);

        $branches = Branch::where('parent_id', null)
            ->whereHas('orders', fn($q) => $q->where('user_id', $user->id))
            ->orWhere(function ($q) use ($user) {
                // Burada bayinin sahip oldugu branchleri bulmak icin farkli bir logic gerekebilir
            })
            ->get();

        $stats = [
            'total_siparisler' => Order::where('user_id', $user->id)->count(),
            'aylik_siparisler' => Order::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMonth())
                ->count(),
        ];

        return view('admin.bayiler.show', compact('user', 'branches', 'stats'));
    }

    public function bayiEdit(User $user)
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $user->load('activeSubscription');

        return view('admin.bayiler.edit', compact('user', 'plans'));
    }

    public function bayiUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Abonelik guncelle
        if (!empty($validated['plan_id'])) {
            $subscription = $user->activeSubscription;
            if ($subscription) {
                $subscription->update(['plan_id' => $validated['plan_id']]);
            } else {
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $validated['plan_id'],
                    'status' => 'active',
                    'starts_at' => now(),
                    'next_billing_date' => now()->addMonth(),
                ]);
            }
        }

        return redirect()
            ->route('admin.bayiler.index')
            ->with('success', 'Bayi basariyla guncellendi.');
    }

    public function bayiDestroy(User $user)
    {
        // Bayi silme - dikkatli olmak lazim
        $user->subscriptions()->delete();
        $user->delete();

        return redirect()
            ->route('admin.bayiler.index')
            ->with('success', 'Bayi basariyla silindi.');
    }

    // ===================================
    // KULLANICILAR
    // ===================================
    public function kullanicilar(Request $request)
    {
        $search = $request->search;
        $activeTab = $request->get('tab', 'adminler');

        // Adminler (super_admin veya admin rolu olanlar)
        $adminlerQuery = User::where(function ($q) {
            $q->whereJsonContains('roles', 'super_admin')
              ->orWhereJsonContains('roles', 'admin');
        });
        if ($search) {
            $adminlerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $adminler = $adminlerQuery->orderBy('created_at', 'desc')->get();

        // Bayiler (bayi rolu olanlar, isletme haric)
        $bayilerQuery = User::whereJsonContains('roles', 'bayi')
            ->whereJsonDoesntContain('roles', 'super_admin')
            ->whereJsonDoesntContain('roles', 'admin');
        if ($search) {
            $bayilerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $bayiler = $bayilerQuery->orderBy('created_at', 'desc')->get();

        // Her bayi icin isletmelerini yukle (parent_id uzerinden)
        $bayiler->each(function ($bayi) {
            $bayi->isletmeler = User::where('parent_id', $bayi->id)
                ->whereJsonContains('roles', 'isletme')
                ->get();
        });

        return view('admin.kullanicilar.index', compact('adminler', 'bayiler', 'activeTab', 'search'));
    }

    public function kullaniciStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:super_admin,admin,bayi,isletme,kurye'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'roles' => $validated['roles'],
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.kullanicilar.index')
            ->with('success', 'Kullanici basariyla olusturuldu.');
    }

    public function kullaniciUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:super_admin,admin,bayi,isletme,kurye'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'roles' => $validated['roles'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()
            ->route('admin.kullanicilar.index')
            ->with('success', 'Kullanici basariyla guncellendi.');
    }

    public function kullaniciDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.kullanicilar.index')
                ->with('error', 'Kendi hesabinizi silemezsiniz.');
        }

        $user->delete();

        return redirect()
            ->route('admin.kullanicilar.index')
            ->with('success', 'Kullanici basariyla silindi.');
    }

    // ===================================
    // SUBELER
    // ===================================
    public function subeler(Request $request)
    {
        $query = Branch::with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $subeler = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.subeler.index', compact('subeler'));
    }

    public function subeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        Branch::create($validated);

        return redirect()
            ->route('admin.subeler.index')
            ->with('success', 'Sube basariyla olusturuldu.');
    }

    public function subeUpdate(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($validated);

        return redirect()
            ->route('admin.subeler.index')
            ->with('success', 'Sube basariyla guncellendi.');
    }

    public function subeDestroy(Branch $branch)
    {
        if ($branch->orders()->exists()) {
            return redirect()
                ->route('admin.subeler.index')
                ->with('error', 'Bu subeye ait siparisler oldugu icin silinemez.');
        }

        $branch->delete();

        return redirect()
            ->route('admin.subeler.index')
            ->with('success', 'Sube basariyla silindi.');
    }

    // ===================================
    // KURYELER
    // ===================================
    public function kuryeler(Request $request)
    {
        $query = Courier::with('owner');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $kuryeler = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.kuryeler.index', compact('kuryeler'));
    }

    public function kuryeShow(Courier $courier)
    {
        $courier->load('orders');

        $stats = [
            'total_teslimat' => $courier->total_deliveries,
            'aktif_siparis' => $courier->active_orders_count,
            'nakit_bakiye' => $courier->cash_balance,
            'ortalama_sure' => $courier->average_delivery_time,
        ];

        $son_siparisler = $courier->orders()->latest()->take(10)->get();

        return view('admin.kuryeler.show', compact('courier', 'stats', 'son_siparisler'));
    }

    public function kuryeUpdate(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:available,busy,offline,on_break'],
            'is_app_enabled' => ['boolean'],
        ]);

        $courier->update($validated);

        return redirect()
            ->route('admin.kuryeler.index')
            ->with('success', 'Kurye basariyla guncellendi.');
    }

    // ===================================
    // SIPARISLER
    // ===================================
    public function siparisler(Request $request)
    {
        $query = Order::with(['branch', 'courier', 'customer']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $siparisler = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.siparisler.index', compact('siparisler'));
    }

    public function siparisShow(Order $order)
    {
        $order->load(['branch', 'courier', 'customer', 'items']);

        return view('admin.siparisler.show', compact('order'));
    }

    // ===================================
    // ABONELIKLER
    // ===================================
    public function abonelikler(Request $request)
    {
        $query = Subscription::with(['user', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $abonelikler = $query->orderBy('created_at', 'desc')->paginate(20);
        $plans = Plan::where('is_active', true)->get();
        $users = User::whereJsonContains('roles', 'bayi')->get();

        return view('admin.abonelikler.index', compact('abonelikler', 'plans', 'users'));
    }

    public function abonelikStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:active,trial,cancelled'],
        ]);

        // Mevcut aktif aboneligi iptal et
        Subscription::where('user_id', $validated['user_id'])
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        Subscription::create([
            'user_id' => $validated['user_id'],
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'starts_at' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);

        return redirect()
            ->route('admin.abonelikler.index')
            ->with('success', 'Abonelik basariyla olusturuldu.');
    }

    public function abonelikUpdate(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,trial,cancelled,expired'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $subscription->update($validated);

        return redirect()
            ->route('admin.abonelikler.index')
            ->with('success', 'Abonelik basariyla guncellendi.');
    }

    // ===================================
    // PLANLAR
    // ===================================
    public function planlar()
    {
        $planlar = Plan::orderBy('sort_order')->get();

        return view('admin.planlar.index', compact('planlar'));
    }

    public function planStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'features' => ['nullable', 'array'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_orders' => ['nullable', 'integer'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        Plan::create($validated);

        return redirect()
            ->route('admin.planlar.index')
            ->with('success', 'Plan basariyla olusturuldu.');
    }

    public function planUpdate(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans,slug,' . $plan->id],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'features' => ['nullable', 'array'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_orders' => ['nullable', 'integer'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        $plan->update($validated);

        return redirect()
            ->route('admin.planlar.index')
            ->with('success', 'Plan basariyla guncellendi.');
    }

    public function planDestroy(Plan $plan)
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()
                ->route('admin.planlar.index')
                ->with('error', 'Bu plana ait aktif abonelikler oldugu icin silinemez.');
        }

        $plan->delete();

        return redirect()
            ->route('admin.planlar.index')
            ->with('success', 'Plan basariyla silindi.');
    }

    // ===================================
    // ENTEGRASYONLAR
    // ===================================
    public function entegrasyonlar()
    {
        $entegrasyonlar = Integration::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.entegrasyonlar.index', compact('entegrasyonlar'));
    }

    // ===================================
    // ISLEMLER
    // ===================================
    public function islemler(Request $request)
    {
        $query = Transaction::with(['user', 'subscription']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $islemler = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.islemler.index', compact('islemler'));
    }

    // ===================================
    // DESTEK
    // ===================================
    public function destek(Request $request)
    {
        $query = SupportTicket::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.destek.index', compact('tickets'));
    }

    public function destekShow(SupportTicket $ticket)
    {
        $ticket->load(['user', 'messages.user']);

        return view('admin.destek.show', compact('ticket'));
    }

    public function destekReply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_admin' => true,
        ]);

        // Durumu guncelle
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return redirect()
            ->route('admin.destek.show', $ticket)
            ->with('success', 'Yanit basariyla gonderildi.');
    }

    public function destekUpdateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,waiting_response,resolved,closed'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'closed_at' => in_array($validated['status'], ['resolved', 'closed']) ? now() : null,
            'closed_by' => in_array($validated['status'], ['resolved', 'closed']) ? auth()->id() : null,
        ]);

        return redirect()
            ->route('admin.destek.index')
            ->with('success', 'Talep durumu guncellendi.');
    }
}
