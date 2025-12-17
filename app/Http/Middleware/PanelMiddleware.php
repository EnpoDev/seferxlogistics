<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip for panel selection and logout routes
        if ($request->routeIs('panel.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if user has active panel in session
        $activePanel = session('active_panel');

        // If no active panel
        if (!$activePanel) {
            // If user has multiple roles, redirect to panel selection
            if ($user->hasMultipleRoles()) {
                return redirect()->route('panel.selection');
            }
            
            // If user has only one role, auto-assign it
            $activePanel = $user->getFirstRole();
            session(['active_panel' => $activePanel]);
        }

        // Validate that user has the active panel role
        if (!in_array($activePanel, $user->roles ?? [])) {
            session()->forget('active_panel');
            
            if ($user->hasMultipleRoles()) {
                return redirect()->route('panel.selection');
            }
            
            $activePanel = $user->getFirstRole();
            session(['active_panel' => $activePanel]);
        }

        // Share active panel with all views
        view()->share('activePanel', $activePanel);
        view()->share('canSwitchPanel', $user->hasMultipleRoles());

        return $next($request);
    }
}
