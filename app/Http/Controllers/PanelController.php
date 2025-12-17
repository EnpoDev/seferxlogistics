<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PanelController extends Controller
{
    /**
     * Show panel selection page
     */
    public function showSelection()
    {
        // If user has only one role, redirect to that panel
        if (!auth()->user()->hasMultipleRoles()) {
            $panel = auth()->user()->getFirstRole();
            return $this->selectPanel($panel);
        }

        return view('auth.panel-secimi');
    }

    /**
     * Set active panel and redirect
     */
    public function selectPanel($panel)
    {
        $user = auth()->user();

        // Validate that user has this role
        if (!in_array($panel, $user->roles ?? [])) {
            return redirect()->route('panel.selection')
                ->with('error', 'Bu panele erişim yetkiniz yok.');
        }

        // Set active panel in session
        session(['active_panel' => $panel]);

        // Redirect to appropriate home page based on panel
        if ($panel === 'bayi') {
            return redirect()->route('bayi.harita');
        } else {
            return redirect()->route('harita');
        }
    }
}
