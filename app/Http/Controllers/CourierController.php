<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Services\CourierAssignmentService;
use App\Events\CourierStatusChanged;
use App\Events\CourierLocationUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourierController extends Controller
{
    protected CourierAssignmentService $assignmentService;

    public function __construct(CourierAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index()
    {
        $couriers = Courier::withCount(['orders as today_deliveries' => function ($query) {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:available,busy,offline,on_break'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'tc_no' => ['nullable', 'string', 'max:11'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i'],
            'max_delivery_minutes' => ['nullable', 'integer', 'min:1'],
            'notification_enabled' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('couriers', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        $validated['notification_enabled'] = $request->boolean('notification_enabled', true);

        $courier = Courier::create($validated);

        // Apply default shifts if enabled
        $businessInfo = \App\Models\BusinessInfo::first();
        if ($businessInfo && $businessInfo->auto_assign_shifts && !empty($businessInfo->default_shifts)) {
            $courier->update(['shifts' => $businessInfo->default_shifts]);
        }

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', 'Kurye başarıyla eklendi.');
        }

        return redirect()->route('isletmem.kuryeler')->with('success', 'Kurye başarıyla eklendi.');
    }

    public function edit(Courier $courier)
    {
        return view('pages.isletmem.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:available,busy,offline,on_break'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'tc_no' => ['nullable', 'string', 'max:11'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i'],
            'max_delivery_minutes' => ['nullable', 'integer', 'min:1'],
            'notification_enabled' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('photo')) {
            if ($courier->photo_path) {
                Storage::disk('public')->delete($courier->photo_path);
            }
            $path = $request->file('photo')->store('couriers', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);
        $validated['notification_enabled'] = $request->boolean('notification_enabled');

        $courier->update($validated);

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', 'Kurye bilgileri güncellendi.');
        }

        return redirect()->route('isletmem.kuryeler')->with('success', 'Kurye bilgileri güncellendi.');
    }

    public function destroy(Courier $courier)
    {
        // Check if courier has active orders
        $activeOrders = $courier->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        if ($activeOrders > 0) {
            return back()->with('error', 'Bu kuryenin aktif siparişleri var. Silemezsiniz.');
        }

        if ($courier->photo_path) {
            Storage::disk('public')->delete($courier->photo_path);
        }

        $courier->delete();

        if (auth()->user()->hasRole('bayi') || session('active_panel') === 'bayi') {
            return redirect()->route('bayi.kuryelerim')->with('success', 'Kurye başarıyla silindi.');
        }

        return redirect()->route('isletmem.kuryeler')->with('success', 'Kurye başarıyla silindi.');
    }

    public function updateStatus(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,busy,offline,on_break'],
        ]);

        $oldStatus = $courier->status;
        $courier->update(['status' => $validated['status']]);

        // Broadcast status change event
        broadcast(new CourierStatusChanged($courier, $oldStatus))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Kurye durumu güncellendi.',
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
        ]);
    }

    /**
     * Update courier shift settings
     */
    public function updateShift(Request $request, Courier $courier)
    {
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
            'message' => 'Vardiya ayarları güncellendi.',
            'courier' => $courier->fresh(),
        ]);
    }

    /**
     * Check if courier is on shift
     */
    public function checkShift(Courier $courier)
    {
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
    public function updateLocation(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $courier->update([
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ]);

        // Broadcast location update event
        broadcast(new CourierLocationUpdated($courier))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Konum güncellendi.',
        ]);
    }
}
