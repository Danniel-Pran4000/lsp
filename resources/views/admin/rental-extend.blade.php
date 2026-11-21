@extends('layouts.header')
@section('title', 'Extend rental')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-stone-200 p-5 sm:p-6" aria-labelledby="current-rental">
            <h2 id="current-rental" class="text-lg font-semibold">Rental summary</h2>
            <dl class="mt-4 divide-y divide-stone-100 text-sm">
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Vehicle</dt>
                    <dd class="font-medium">{{ $rental->brand }} {{ $rental->vehicle_model }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Unit</dt>
                    <dd class="font-medium">{{ $rental->garage->code }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Renter</dt>
                    <dd class="font-medium">{{ $rental->user->name }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Original rental type</dt>
                    <dd>{{ $rental->rental_type === 'monthly' ? 'Monthly' : 'Daily' }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Current end date</dt>
                    <dd>{{ $rental->ends_on }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2 py-3">
                    <dt class="text-stone-600">Original daily rate</dt>
                    <dd class="tabular-nums">IDR {{ str_replace('.', ',', $rental->daily_rate) }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-stone-200 p-5 sm:p-6" aria-labelledby="extension-period">
            <h2 id="extension-period" class="text-lg font-semibold">Extension period</h2>
            <form id="extension-period-form" class="mt-4" action="{{ route('extensions.preview', $rental) }}" method="get">
                <label class="block text-sm font-medium">New end date
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="date" name="ends_on" min="{{ $earliestEnd }}" max="9999-12-31"
                        value="{{ is_string(old('ends_on')) ? old('ends_on') : $newEnd }}" required>
                </label>
            </form>
            @if ($charge)
                <button form="extension-period-form" class="mt-4 text-sm font-medium text-red-700 underline-offset-4 hover:underline" type="submit">Recalculate additional charge</button>
            @endif
        </section>
    </div>

    @if ($charge)
        <form id="extension-payment-form" action="{{ route('extensions.store', $rental) }}" method="post" enctype="multipart/form-data"
            class="mt-6 grid gap-5 rounded-xl border border-red-100 bg-red-50/50 p-5 sm:p-6 lg:grid-cols-2">
            @csrf
            <input type="hidden" name="quote_id" value="{{ $quoteId }}">
            <div>
                <h2 class="text-lg font-semibold">Extension payment</h2>
                <p class="mt-2 text-sm text-stone-600">{{ $charge['days'] }} days · New end date {{ $newEnd }}</p>
                <p class="mt-2 text-2xl font-semibold text-red-800 tabular-nums">IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['amount']) }}</p>
            </div>
            <div>
                <label for="extension-proof" class="block text-sm font-medium">Payment proof</label>
                <p id="extension-proof-hint" class="mt-1 text-xs text-stone-600">Upload an image, maximum 2 MB.</p>
                <input id="extension-proof" class="mt-2 block w-full rounded-lg border border-stone-300 bg-white text-sm file:me-3 file:border-0 file:bg-stone-100 file:px-3 file:py-2.5 file:font-medium focus-visible:outline-2 focus-visible:outline-red-700"
                    type="file" name="proof" accept="image/*" aria-describedby="extension-proof-hint" required>
            </div>
        </form>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
        <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('admin.rentals') }}">Back to rentals</a>
        @if ($charge)
            <button form="extension-payment-form" class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Save extension</button>
        @else
            <button form="extension-period-form" class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Calculate additional charge</button>
        @endif
    </div>
@endsection
