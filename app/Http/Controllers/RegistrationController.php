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
        return view('auth.register');
    }

    public function register(RegisterUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return to_route('login')->with('status', 'Pendaftaran berhasil. Akun Anda menunggu persetujuan admin.');
    }

    public function index(): View
    {
        $users = User::where('role', 'user')
            ->orderBy('id')
            ->simplePaginate(15);

        return view('admin.users', compact('users'));
    }

    public function verify(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 404);

        ['decision' => $decision] = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        User::whereKey($user->id)->where('role', 'user')->update([
            'approval_status' => $decision,
        ]);

        $status = $decision === 'approved' ? 'disetujui' : 'ditolak';

        return to_route('registration.index')->with('status', "Pendaftaran {$user->name} {$status}.");
    }
}
