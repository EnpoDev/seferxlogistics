<?php

namespace App\Http\Controllers;

use App\Models\BusinessInfo;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function general()
    {
        $businessInfo = BusinessInfo::first();
        $user = auth()->user();
        
        return view('pages.ayarlar.genel', compact('businessInfo', 'user'));
    }

    public function application()
    {
        return view('pages.ayarlar.uygulama');
    }

    public function payment()
    {
        return view('pages.ayarlar.odeme');
    }

    public function printer()
    {
        return view('pages.ayarlar.yazici');
    }

    public function notification()
    {
        $settings = NotificationSetting::getOrCreateForUser(auth()->id());
        return view('pages.ayarlar.bildirim', compact('settings'));
    }

    public function updateNotification(Request $request)
    {
        $settings = NotificationSetting::getOrCreateForUser(auth()->id());
        
        $settings->update([
            'new_order_notification' => $request->boolean('new_order_notification'),
            'order_status_notification' => $request->boolean('order_status_notification'),
            'order_cancelled_notification' => $request->boolean('order_cancelled_notification'),
            'email_daily_summary' => $request->boolean('email_daily_summary'),
            'email_weekly_report' => $request->boolean('email_weekly_report'),
            'email_new_order' => $request->boolean('email_new_order'),
            'push_enabled' => $request->boolean('push_enabled'),
            'push_new_order' => $request->boolean('push_new_order'),
            'push_order_status' => $request->boolean('push_order_status'),
            'sms_enabled' => $request->boolean('sms_enabled'),
            'sms_new_order' => $request->boolean('sms_new_order'),
            'sound_enabled' => $request->boolean('sound_enabled'),
        ]);
        
        return redirect()
            ->route('ayarlar.bildirim')
            ->with('success', 'Bildirim ayarları güncellendi.');
    }

    public function cashRegister()
    {
        return view('pages.ayarlar.yazarkasa');
    }

    public function theme()
    {
        return view('pages.tema');
    }

    public function support()
    {
        return view('pages.destek');
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
        ]);

        auth()->user()->update($validated);

        return redirect()
            ->route('ayarlar.genel')
            ->with('success', 'Kullanıcı bilgileri güncellendi.');
    }

    public function updateBusiness(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $businessInfo = BusinessInfo::first();

        if (!$businessInfo) {
            $businessInfo = new BusinessInfo();
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($businessInfo->logo) {
                \Storage::disk('public')->delete($businessInfo->logo);
            }
            $validated['logo'] = $request->file('logo')->store('business', 'public');
        }

        $businessInfo->fill($validated);
        $businessInfo->save();

        return redirect()
            ->route('ayarlar.genel')
            ->with('success', 'İşletme bilgileri güncellendi.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ]);

        if (!\Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifre hatalı.']);
        }

        auth()->user()->update([
            'password' => \Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('ayarlar.genel')
            ->with('success', 'Şifre başarıyla değiştirildi.');
    }
}

