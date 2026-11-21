<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('registration.create');
    }

    public function register(RegisterUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return to_route('login')->with('status', 'Pendaftaran berhasil. Akun menunggu verifikasi admin.');
    }

    public function pending(): View
    {
        $users = User::where('role', 'user')
            ->where('approval_status', 'pending')
            ->orderBy('id')
            ->simplePaginate(20);

        return view('registration.pending', compact('users'));
    }

    public function verify(Request $request, User $user): RedirectResponse
    {
        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        // Batasi perubahan ke akun yang masih pending, termasuk saat admin bertindak bersamaan.
        $updated = User::whereKey($user->id)
            ->where('role', 'user')
            ->where('approval_status', 'pending')
            ->update(['approval_status' => $decision['decision']]);

        abort_if($updated === 0, 409, 'Pendaftar sudah diproses atau tidak dapat diverifikasi.');

        return to_route('registration.pending')->with('status', 'Keputusan verifikasi disimpan.');
    }
}
