@extends('layouts.auth')
@section('title', 'Create an account')

@section('content')
    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Create an account</h1>
    <p class="mt-3 text-sm leading-6 text-stone-600">Register as a renter. An admin will review your account before you can book a garage.</p>

    <form class="mt-8 space-y-5" method="post" action="{{ route('registration.store') }}">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-neutral-900">Full name</label>
            <input id="name" name="name" autocomplete="name" value="{{ old('name') }}"
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                @error('name') aria-describedby="name-error" @enderror
                @error('name') aria-invalid="true" @enderror required>
            @error('name')
                <p id="name-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="nik" class="block text-sm font-medium text-neutral-900">National ID (NIK)</label>
                <p id="nik-hint" class="mt-1 text-xs leading-5 text-stone-600">Enter your 16-digit ID number.</p>
                <input id="nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" value="{{ old('nik') }}"
                    class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                    aria-describedby="nik-hint @error('nik') nik-error @enderror"
                    @error('nik') aria-invalid="true" @enderror required>
                @error('nik')
                    <p id="nik-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-neutral-900">Phone number</label>
                <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}"
                    class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                    @error('phone') aria-describedby="phone-error" @enderror
                    @error('phone') aria-invalid="true" @enderror required>
                @error('phone')
                    <p id="phone-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-neutral-900">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}"
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                @error('email') aria-describedby="email-error" @enderror
                @error('email') aria-invalid="true" @enderror required>
            @error('email')
                <p id="email-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-900">Password</label>
            <p id="password-hint" class="mt-1 text-xs leading-5 text-stone-600">Use at least 8 characters.</p>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8"
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-4 py-3 text-base text-neutral-900 focus:border-red-700 focus:outline-2 focus:outline-red-700 sm:text-sm"
                aria-describedby="password-hint @error('password') password-error @enderror"
                @error('password') aria-invalid="true" @enderror required>
            @error('password')
                <p id="password-error" class="mt-2 text-sm text-red-800" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button class="w-full rounded-lg bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Register</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-600">
        Already have an account? <a class="font-semibold text-red-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-700" href="{{ route('login') }}">Sign in</a>
    </p>
@endsection
