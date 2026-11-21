@extends('layouts.account')
@section('title', 'Create garage units')

@section('content')
    @if (session('garage_codes'))
        <p class="my-3 text-sm leading-6">Created unit codes</p>
        <p class="my-3 text-sm leading-6">{{ implode(', ', session('garage_codes')) }}</p>
    @endif

    <form method="post" action="{{ route('garages.store-batch') }}">
        @csrf
        <label class="my-4 block text-sm font-medium text-neutral-700" for="type">Garage type</label>
        <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="type" name="type" required>
            <option value="S" @selected(old('type') === 'S')>Bicycle</option>
            <option value="C" @selected(old('type') === 'C')>Petrol car</option>
            <option value="E" @selected(old('type') === 'E')>Electric car</option>
        </select>
        <label class="my-4 block text-sm font-medium text-neutral-700">Number of units
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="quantity" min="1" max="999" value="{{ old('quantity') }}" required>
        </label>
        <label class="my-4 block text-sm font-medium text-neutral-700">Daily rate (Rp)
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="daily_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('daily_rate') }}" required>
        </label>
        <label class="my-4 block text-sm font-medium text-neutral-700" id="kwh-rate-field">Rate per kWh (IDR, electric garages only)
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="kwh_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('kwh_rate') }}">
        </label>
        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Create units</button>
    </form>
    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('registration.index') }}">Users</a></p>

    
@endsection
