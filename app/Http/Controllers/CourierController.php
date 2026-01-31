<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Services\CourierAssignmentService;
use App\Events\CourierStatusChanged;
use App\Events\CourierLocationUpdated;
use App\Http\Requests\Courier\StoreCourierRequest;
use App\Http\Requests\Courier\UpdateCourierRequest;
use App\Http\Requests\Courier\UpdateCourierStatusRequest;
use App\Http\Requests\Courier\UpdateCourierLocationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourierController extends Controller
{
    protected CourierAssignmentService $assignmentService;

    public function __construct(CourierAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get the bayi user ID for the current user
     * If user is işletme, return their parent (bayi) ID
     * If user is bayi, return their own ID
     */
    private function getBayiUserId(): int
    {
        $user = auth()->user();

        // If user is işletme, their couriers belong to their parent (bayi)
        if ($user->hasRole('isletme') && $user->parent_id) {
            return $user->parent_id;
        }

        return $user->id;
    }

    /**
     * Check if the authenticated user owns this courier
     */
    private function checkCourierOwnership(Courier $courier): void
    {
        if ($courier->user_id !== $this->getBayiUserId()) {
            abort(403, 'Bu kuryeye erişim yetkiniz yok.');
        }
    }

    public function index()
    {
        $bayiUserId = $this->getBayiUserId();

        $couriers = Courier::where('user_id', $bayiUserId)
            ->withCount(['orders as today_deliveries' => function ($query) {
                $query->whereDate('created_at', today());
            }])
            ->orderBy('name')
            ->get();

        $stats = $this->assignmentService->getCourierWorkloadStats();

        return view('pages.isletmem.kuryeler', compact('couriers', 'stats'));
    }

    public function create()
    {
        return view('pages.isletmem.couriers.create');
    }

    public function store(StoreCourierRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('couriers', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        $validated['notification_enabled'] = $request->boolean('notification_enabled', true);
        $validated['can_reject_package'] = $request->boolean('can_reject_package', true);
        $validated['payment_editing_enabled'] = $request->boolean('payment_editing_enabled', true);
        $validated['status_change_enabled'] = $request->boolean('status_change_enabled', true);

        // Set the owner (bayi) user_id
        $validated['user_id'] = $this->getBayiUserId();

        // Sifre ayarlandiysa app erisimini ac
        if (!empty($validated['password'])) {
            $validated['is_app_enabled'] = true;
        }

        $courier = Courier::create($validated);

        // Apply default shifts if enabled
        $businessInfo = \App\Models\BusinessInfo::first();
        if ($businessInfo && $businessInfo->auto_assign_shifts && !empty($businessInfo->default_shifts)) {
            $courier->update(['shifts' => $businessInfo->default_shifts]);
        }

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', __('messages.success.courier_created'));
        }

        return redirect()->route('isletmem.kuryeler')->with('success', __('messages.success.courier_created'));
    }

    public function edit(Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        return view('pages.isletmem.couriers.edit', compact('courier'));
    }

    public function update(UpdateCourierRequest $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            if ($courier->photo_path) {
                Storage::disk('public')->delete($courier->photo_path);
            }
            $path = $request->file('photo')->store('couriers', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        $validated['notification_enabled'] = $request->boolean('notification_enabled');
        $validated['can_reject_package'] = $request->boolean('can_reject_package');
        $validated['payment_editing_enabled'] = $request->boolean('payment_editing_enabled');
        $validated['status_change_enabled'] = $request->boolean('status_change_enabled');

        // Sifre bos degilse guncelle
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['is_app_enabled'] = true;
        }

        $courier->update($validated);

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', __('messages.success.courier_updated'));
        }

        return redirect()->route('isletmem.kuryeler')->with('success', __('messages.success.courier_updated'));
    }

    public function destroy(Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        // Check if courier has active orders
        $activeOrders = $courier->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        if ($activeOrders > 0) {
            return back()->with('error', __('messages.error.courier_has_active_orders'));
        }

        if ($courier->photo_path) {
            Storage::disk('public')->delete($courier->photo_path);
        }

        $courier->delete();

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', __('messages.success.courier_deleted'));
        }

        return redirect()->route('isletmem.kuryeler')->with('success', __('messages.success.courier_deleted'));
    }

    public function updateStatus(UpdateCourierStatusRequest $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validated();

        $oldStatus = $courier->status;
        $courier->update(['status' => $validated['status']]);

        // Broadcast status change event
        broadcast(new CourierStatusChanged($courier, $oldStatus))->toOthers();

        return response()->json([
            'success' => true,
            'message' => __('messages.success.courier_status_updated'),
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
        ]);
    }

    /**
     * Update courier shift settings
     */
    public function updateShift(Request $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validate([
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i'],
            'max_delivery_minutes' => ['nullable', 'integer', 'min:1'],
            'notification_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['notification_enabled'] = $request->boolean('notification_enabled');

        $courier->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.success.shift_updated'),
            'courier' => $courier->fresh(),
        ]);
    }

    /**
     * Check if courier is on shift
     */
    public function checkShift(Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        return response()->json([
            'is_on_shift' => $courier->isOnShift(),
            'can_receive_notifications' => $courier->canReceiveNotification(),
            'shift_start' => $courier->shift_start,
            'shift_end' => $courier->shift_end,
        ]);
    }

    /**
     * Get available couriers for order assignment
     */
    public function getAvailable()
    {
        $couriers = $this->assignmentService->getAvailableCouriers();

        return response()->json([
            'couriers' => $couriers->map(function ($courier) {
                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'status' => $courier->status,
                    'status_label' => $courier->getStatusLabel(),
                    'active_orders_count' => $courier->active_orders_count,
                    'is_on_shift' => $courier->isOnShift(),
                    'lat' => $courier->lat,
                    'lng' => $courier->lng,
                ];
            }),
        ]);
    }

    /**
     * Get courier workload statistics
     */
    public function getStats()
    {
        $stats = $this->assignmentService->getCourierWorkloadStats();

        return response()->json($stats);
    }

    /**
     * Update courier location
     */
    public function updateLocation(UpdateCourierLocationRequest $request, Courier $courier)
    {
        $validated = $request->validated();

        $courier->update([
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ]);

        // Broadcast location update event
        broadcast(new CourierLocationUpdated($courier))->toOthers();

        return response()->json([
            'success' => true,
            'message' => __('messages.success.location_updated'),
        ]);
    }
}
