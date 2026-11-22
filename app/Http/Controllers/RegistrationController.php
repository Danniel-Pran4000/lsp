<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran akun penyewa.
     */
    public function showForm(): View
    {
        return view('auth.register');
    }

    /**
     * Simpan pendaftar menggunakan status awal pending agar admin dapat memeriksa identitasnya.
     */
    public function registerRenter(RegisterUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return to_route('login')->with('status', 'Pendaftaran berhasil. Akun Anda menunggu persetujuan admin.');
    }

    /**
     * Tampilkan semua penyewa sehingga admin dapat meninjau kembali keputusan sebelumnya.
     */
    public function listRenters(): View
    {
        $users = User::where('role', 'user')
            ->orderBy('id')
            ->simplePaginate(15);

        return view('admin.users', compact('users'));
    }

    /**
     * Simpan keputusan persetujuan pada akun yang sama agar riwayat sewanya tetap dapat ditelusuri.
     */
    public function reviewRenter(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isRenter(), 404);

        ['decision' => $decision] = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        User::whereKey($user->id)->where('role', 'user')->update([
            'approval_status' => $decision,
        ]);

        $status = $decision === 'approved' ? 'disetujui' : 'ditolak';

        return to_route('registration.index')->with('status', "Pendaftaran {$user->name} {$status}.");
    }
}
