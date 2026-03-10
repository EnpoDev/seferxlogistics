<?php

namespace App\Http\Controllers;

use App\Models\CallerIdLog;
use Illuminate\Http\Request;

class CallerIdLogController extends Controller
{
    /**
     * Display recent calls widget data for dashboard
     */
    public function getRecentCalls(Request $request)
    {
        $user = auth()->user();

        // Get active branch based on user role
        $activeBranch = null;
        if ($user->role === 'bayi') {
            // For bayi, get main branch
            $activeBranch = \App\Models\Branch::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->first();
        } elseif ($user->role === 'isletme') {
            // For işletme, get their branch
            $activeBranch = \App\Models\Branch::where('user_id', $user->id)->first();
        }

        if (!$activeBranch) {
            return response()->json([]);
        }

        $calls = CallerIdLog::forBranch($activeBranch->id)
            ->with('customer')
            ->recent(10)
            ->get()
            ->map(function($call) {
                return [
                    'id' => $call->id,
                    'phone' => $call->formatted_phone,
                    'caller_name' => $call->caller_display_name,
                    'customer_type' => $call->customer?->customer_type,
                    'time' => $call->created_at->diffForHumans(),
                    'has_customer' => !is_null($call->customer_id),
                ];
            });

        return response()->json($calls);
    }

    /**
     * Display full call history page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get active branch based on user role
        $activeBranch = null;
        if ($user->role === 'bayi') {
            // For bayi, get main branch
            $activeBranch = \App\Models\Branch::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->first();
        } elseif ($user->role === 'isletme') {
            // For işletme, get their branch
            $activeBranch = \App\Models\Branch::where('user_id', $user->id)->first();
        }

        if (!$activeBranch) {
            abort(403, 'Aktif işletme bulunamadı');
        }

        $query = CallerIdLog::forBranch($activeBranch->id)
            ->with('customer');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('customer_type')) {
            if ($request->customer_type === 'registered') {
                $query->whereNotNull('customer_id');
            } else {
                $query->whereNull('customer_id');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $calls = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('isletmem.aramalar', compact('calls'));
    }
}
