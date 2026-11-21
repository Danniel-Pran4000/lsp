@extends('layouts.header')
@section('title', 'Create garage units')

@section('content')
    <form method="post" action="{{ route('garages.store-batch') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        {{-- Keep creation as the default Enter action. --}}
        <button type="submit" hidden>Create units</button>

        <div class="grid gap-6 lg:grid-cols-2">
            <fieldset class="min-w-0 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold">Unit details</legend>
                <fieldset>
                    <legend class="text-sm font-medium">Garage type</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['S' => 'Bicycle', 'C' => 'Petrol car', 'E' => 'Electric car'] as $code => $label)
                            <button type="submit" name="type_selection" value="{{ $code }}" formnovalidate
                                aria-pressed="{{ $type === $code ? 'true' : 'false' }}"
                                class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:border-red-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700 aria-pressed:border-red-700 aria-pressed:bg-red-50 aria-pressed:text-red-800">{{ $label }}</button>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-4 block text-sm font-medium">Number of units
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="quantity" min="1" max="999" value="{{ old('quantity') }}" required>
                </label>
            </fieldset>

            <fieldset class="min-w-0 space-y-4 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold">Rates</legend>
                <label class="block text-sm font-medium">Daily rate (IDR)
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="daily_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('daily_rate') }}" required>
                </label>

                <label class="block text-sm font-medium">Rate per kWh (IDR)
                    <span class="block text-xs text-stone-600">Electric garages only.</span>
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="kwh_rate" min="0" max="999999999999.99" step="0.01"
                        value="{{ $type === 'E' ? old('kwh_rate') : '' }}" @disabled($type !== 'E') @required($type === 'E')>
                </label>
            </fieldset>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('admin.rentals') }}">Back to rentals</a>
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Create units</button>
        </div>
    </form>
@endsection
