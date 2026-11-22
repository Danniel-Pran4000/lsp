<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SessionController extends Controller
{
    /**
     * Tampilkan formulir masuk untuk admin dan penyewa.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Periksa kredensial dan perbarui ID sesi setelah login berhasil.
     */
    public function startSession(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectAccount($request);
    }

    /**
     * Arahkan pengguna ke daftar penyewa atau riwayat sewanya sesuai peran akun.
     */
    public function redirectAccount(Request $request): RedirectResponse
    {
        return $request->user()->isAdmin()
            ? to_route('registration.index')
            : to_route('rentals.index');
    }

    /**
     * Akhiri autentikasi dan batalkan sesi agar sesi lama tidak dapat digunakan kembali.
     */
    public function endSession(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
