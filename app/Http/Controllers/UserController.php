<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Admin/Super Admin tüm kullanıcıları görebilir
        if ($user->isAdmin()) {
            $users = User::orderBy('name')->paginate(20);
        }
        // Bayi sadece kendi oluşturduğu kullanıcıları görebilir
        elseif ($user->isBayi()) {
            $users = User::where('parent_id', $user->id)
                ->orWhere('id', $user->id)
                ->orderBy('name')
                ->paginate(20);
        }
        // İşletme sadece kendini görebilir
        else {
            $users = User::where('id', $user->id)->paginate(20);
        }

        return view('pages.isletmem.kullanicilar', compact('users'));
    }

    public function create()
    {
        return view('pages.isletmem.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:bayi,isletme'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        // Oluşturan kullanıcıyı parent olarak ata (admin değilse)
        $authUser = auth()->user();
        if (!$authUser->isAdmin()) {
            $validated['parent_id'] = $authUser->id;
        }

        $user = User::create($validated);

        return redirect()
            ->route('isletmem.kullanicilar')
            ->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    public function edit(User $user)
    {
        $authUser = auth()->user();

        // Yetki kontrolü
        if (!$authUser->isAdmin() && $user->parent_id !== $authUser->id && $user->id !== $authUser->id) {
            abort(403, 'Bu kullanıcıyı düzenleme yetkiniz yok.');
        }

        return view('pages.isletmem.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        // Yetki kontrolü
        if (!$authUser->isAdmin() && $user->parent_id !== $authUser->id && $user->id !== $authUser->id) {
            abort(403, 'Bu kullanıcıyı düzenleme yetkiniz yok.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:bayi,isletme'],
        ]);

        // Only update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('isletmem.kullanicilar')
            ->with('success', 'Kullanıcı bilgileri güncellendi.');
    }

    public function destroy(User $user)
    {
        $authUser = auth()->user();

        // Prevent deleting yourself
        if ($user->id === $authUser->id) {
            return redirect()
                ->route('isletmem.kullanicilar')
                ->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        // Yetki kontrolü
        if (!$authUser->isAdmin() && $user->parent_id !== $authUser->id) {
            return redirect()
                ->route('isletmem.kullanicilar')
                ->with('error', 'Bu kullanıcıyı silme yetkiniz yok.');
        }

        $user->delete();

        return redirect()
            ->route('isletmem.kullanicilar')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }
}

