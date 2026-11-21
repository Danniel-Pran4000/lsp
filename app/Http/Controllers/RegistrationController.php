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

        return to_route('login')->with('status', 'Registration successful. Your account is awaiting approval.');
    }

    public function index(): View
    {
        $users = User::where('role', 'user')
            ->orderBy('id')
            ->simplePaginate(20);

        return view('admin.users', compact('users'));
    }

    public function verify(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 404);

        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        User::whereKey($user->id)->where('role', 'user')->update([
            'approval_status' => $decision['decision'],
        ]);

        return to_route('registration.index')->with('status', 'User status updated.');
    }
}
