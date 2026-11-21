@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Sign in</h1>
    <p class="mt-3 text-sm leading-6 text-stone-600">Enter your email and password to access your account.</p>

    <form class="mt-8 space-y-5" method="post" action="{{ route('login.store') }}">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-neutral-900">Email address</label>
            <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}"
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                @error('email') aria-describedby="email-error" @enderror
                @error('email') aria-invalid="true" @enderror required>
            @error('email')
                <p id="email-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-900">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password"
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                @error('password') aria-describedby="password-error" @enderror
                @error('password') aria-invalid="true" @enderror required>
            @error('password')
                <p id="password-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button class="w-full rounded-lg bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Sign in</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-600">
        New to Garage Rental? <a class="font-semibold text-red-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-700" href="{{ route('register') }}">Create an account</a>
    </p>
@endsection
