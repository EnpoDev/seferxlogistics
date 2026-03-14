<?php

namespace App\Http\Controllers;

use App\Models\ApplicationSetting;
use App\Models\BusinessInfo;
use App\Models\CashRegisterSetting;
use App\Models\NotificationSetting;
use App\Models\PaymentSetting;
use App\Models\PrinterSetting;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $settings = ApplicationSetting::getOrCreateForUser(auth()->id());
        $timezones = ApplicationSetting::getTimezones();
        return view('pages.ayarlar.uygulama', compact('settings', 'timezones'));
    }

    public function updateApplication(Request $request)
    {
        $validated = $request->validate([
            'language' => ['required', 'string', Rule::in(['tr', 'en'])],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'currency' => ['required', 'string', Rule::in(['TRY', 'USD', 'EUR'])],
            'auto_accept_orders' => ['nullable', 'boolean'],
            'sound_notifications' => ['nullable', 'boolean'],
            'default_order_timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'default_preparation_time' => ['required', 'integer', 'min:5', 'max:120'],
        ]);

        $settings = ApplicationSetting::getOrCreateForUser(auth()->id());

        $settings->update([
            'language' => $validated['language'],
            'timezone' => $validated['timezone'],
            'currency' => $validated['currency'],
            'auto_accept_orders' => $request->boolean('auto_accept_orders'),
            'sound_notifications' => $request->boolean('sound_notifications'),
            'default_order_timeout' => $validated['default_order_timeout'],
            'default_preparation_time' => $validated['default_preparation_time'],
        ]);
        
        return redirect()
            ->route('ayarlar.uygulama')
            ->with('success', 'Uygulama ayarları güncellendi.');
    }

    public function payment()
    {
        $settings = PaymentSetting::getOrCreateForUser(auth()->id());
        return view('pages.ayarlar.odeme', compact('settings'));
    }

    public function updatePayment(Request $request)
    {
        $validated = $request->validate([
            'accept_cash' => ['nullable', 'boolean'],
            'accept_card' => ['nullable', 'boolean'],
            'accept_card_on_delivery' => ['nullable', 'boolean'],
            'accept_online' => ['nullable', 'boolean'],
            'payment_provider' => ['nullable', 'string', Rule::in(['iyzico', 'paytr', 'stripe', 'none'])],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'max_cash_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        $settings = PaymentSetting::getOrCreateForUser(auth()->id());

        $settings->update([
            'accept_cash' => $request->boolean('accept_cash'),
            'accept_card' => $request->boolean('accept_card'),
            'accept_card_on_delivery' => $request->boolean('accept_card_on_delivery'),
            'accept_online' => $request->boolean('accept_online'),
            'payment_provider' => $validated['payment_provider'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'max_cash_amount' => $validated['max_cash_amount'] ?? null,
        ]);
        
        return redirect()
            ->route('ayarlar.odeme')
            ->with('success', 'Ödeme ayarları güncellendi.');
    }

    public function printer()
    {
        $printers = PrinterSetting::where('user_id', auth()->id())->get();
        return view('pages.ayarlar.yazici', compact('printers'));
    }

    public function storePrinter(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:kitchen,receipt,label'],
            'connection_type' => ['required', 'in:usb,network,bluetooth'],
            'ip_address' => ['nullable', 'required_if:connection_type,network', 'ip'],
            'port' => ['nullable', 'integer'],
            'model' => ['nullable', 'string', 'max:255'],
            'auto_print' => ['boolean'],
            'copies' => ['integer', 'min:1', 'max:10'],
            'paper_width' => ['in:58mm,80mm'],
        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_active'] = true;
        $validated['print_on_new_order'] = $request->boolean('print_on_new_order', true);

        PrinterSetting::create($validated);

        return redirect()
            ->route('ayarlar.yazici')
            ->with('success', 'Yazıcı başarıyla eklendi.');
    }

    public function updatePrinter(Request $request, PrinterSetting $printer)
    {
        if ($printer->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'connection_type' => ['required', 'in:usb,network,bluetooth'],
            'ip_address' => ['nullable', 'required_if:connection_type,network'],
            'port' => ['nullable', 'integer'],
            'model' => ['nullable', 'string', 'max:255'],
            'auto_print' => ['boolean'],
            'copies' => ['integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['auto_print'] = $request->boolean('auto_print');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['print_on_new_order'] = $request->boolean('print_on_new_order');

        $printer->update($validated);

        return redirect()
            ->route('ayarlar.yazici')
            ->with('success', 'Yazıcı ayarları güncellendi.');
    }

    public function destroyPrinter(PrinterSetting $printer)
    {
        if ($printer->user_id !== auth()->id()) {
            abort(403);
        }

        $printer->delete();

        return redirect()
            ->route('ayarlar.yazici')
            ->with('success', 'Yazıcı başarıyla silindi.');
    }

    public function testPrinter(PrinterSetting $printer)
    {
        if ($printer->user_id !== auth()->id()) {
            abort(403);
        }

        $success = $printer->testConnection();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Bağlantı başarılı!' : 'Bağlantı kurulamadı.',
        ]);
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
        $settings = CashRegisterSetting::getOrCreateForUser(auth()->id());
        return view('pages.ayarlar.yazarkasa', compact('settings'));
    }

    public function updateCashRegister(Request $request)
    {
        $settings = CashRegisterSetting::getOrCreateForUser(auth()->id());
        
        $validated = $request->validate([
            'model' => ['nullable', 'in:hugin,olivetti,ingenico,custom'],
            'connection_type' => ['required', 'in:serial,ethernet,usb'],
            'port' => ['nullable', 'string', 'max:50'],
            'baud_rate' => ['nullable', 'integer'],
            'default_vat_rate' => ['required', 'integer', 'in:1,8,10,18,20'],
        ]);

        $settings->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'model' => $validated['model'],
            'connection_type' => $validated['connection_type'],
            'port' => $validated['port'],
            'baud_rate' => $validated['baud_rate'],
            'default_vat_rate' => $validated['default_vat_rate'],
            'auto_send_orders' => $request->boolean('auto_send_orders'),
        ]);
        
        return redirect()
            ->route('ayarlar.yazarkasa')
            ->with('success', 'Yazarkasa ayarları güncellendi.');
    }

    public function testCashRegister()
    {
        $settings = CashRegisterSetting::getOrCreateForUser(auth()->id());
        $success = $settings->testConnection();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Bağlantı başarılı!' : 'Bağlantı kurulamadı. Ayarları kontrol edin.',
        ]);
    }

    public function theme()
    {
        $settings = ThemeSetting::getOrCreateForUser(auth()->id());
        return view('pages.tema', compact('settings'));
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_mode' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
            'compact_mode' => ['nullable', 'boolean'],
            'animations_enabled' => ['nullable', 'boolean'],
            'sidebar_auto_hide' => ['nullable', 'boolean'],
            'sidebar_width' => ['required', 'string', Rule::in(['narrow', 'normal', 'wide'])],
        ]);

        $settings = ThemeSetting::getOrCreateForUser(auth()->id());

        $settings->update([
            'theme_mode' => $validated['theme_mode'],
            'compact_mode' => $request->boolean('compact_mode'),
            'animations_enabled' => $request->boolean('animations_enabled'),
            'sidebar_auto_hide' => $request->boolean('sidebar_auto_hide'),
            'sidebar_width' => $validated['sidebar_width'],
        ]);
        
        return redirect()
            ->route('tema')
            ->with('success', 'Tema ayarları güncellendi.');
    }

    public function support()
    {
        $tickets = auth()->user()->supportTickets ?? collect([]);
        return view('pages.destek', compact('tickets'));
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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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

