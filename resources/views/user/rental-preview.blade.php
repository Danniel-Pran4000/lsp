@extends('layouts.header')
@section('title', 'Review payment')

@section('content')
    <form method="post" action="{{ route('rentals.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="quote_id" value="{{ $quoteId }}">

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-stone-200 p-5 sm:p-6" aria-labelledby="rental-summary">
                <h2 id="rental-summary" class="text-lg font-semibold">Rental summary</h2>
                <dl class="mt-4 divide-y divide-stone-100 text-sm">
                    <div class="flex flex-wrap justify-between gap-2 py-3">
                        <dt class="text-stone-600">Unit code</dt>
                        <dd class="rounded-md bg-stone-100 px-2 font-semibold">{{ $garage->code }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 py-3">
                        <dt class="text-stone-600">Rental period</dt>
                        <dd class="font-medium">{{ $start->format('d/m/Y') }} to {{ $end->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 py-3">
                        <dt class="text-stone-600">Daily rate</dt>
                        <dd class="tabular-nums">IDR {{ str_replace('.', ',', $charge['daily_rate']) }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 py-3">
                        <dt class="text-stone-600">Rental charge</dt>
                        <dd class="tabular-nums">IDR {{ str_replace('.', ',', $charge['rental_amount']) }}</dd>
                    </div>
                    @if ($charge['kwh_rate'] !== null)
                        <div class="flex flex-wrap justify-between gap-2 py-3">
                            <dt class="text-stone-600">Rate per kWh</dt>
                            <dd class="tabular-nums">IDR {{ str_replace('.', ',', $charge['kwh_rate']) }}</dd>
                        </div>
                        <div class="flex flex-wrap justify-between gap-2 py-3">
                            <dt class="text-stone-600">Estimated energy</dt>
                            <dd class="tabular-nums">{{ str_replace('.', ',', rtrim(rtrim($charge['energy_kwh'], '0'), '.')) }} kWh</dd>
                        </div>
                        <div class="flex flex-wrap justify-between gap-2 py-3">
                            <dt class="text-stone-600">Estimated electricity charge</dt>
                            <dd class="tabular-nums">IDR {{ str_replace('.', ',', rtrim(rtrim($charge['electricity_amount'], '0'), '.')) }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="rounded-xl border border-red-100 bg-red-50/50 p-5 sm:p-6" aria-labelledby="payment-details">
                <h2 id="payment-details" class="text-lg font-semibold">Payment details</h2>
                <dl class="mt-4 rounded-lg border border-red-100 bg-white p-4">
                    <dt class="text-sm text-stone-600">Total payment</dt>
                    <dd class="mt-1 text-2xl font-semibold break-words text-red-800 tabular-nums">IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['initial_total']) }}</dd>
                </dl>
                <label for="proof" class="mt-5 block text-sm font-medium">Payment proof</label>
                <p id="proof-hint" class="mt-1 text-xs text-stone-600">Upload an image, maximum 2 MB.</p>
                <input id="proof" class="mt-2 block w-full rounded-lg border border-stone-300 bg-white text-sm text-neutral-900 file:me-3 file:border-0 file:bg-stone-100 file:px-3 file:py-2.5 file:font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700"
                    type="file" name="proof" accept="image/*" aria-describedby="proof-hint" required>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('rentals.create') }}">Back to rental form</a>
            <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Confirm rental</button>
        </div>
    </form>
@endsection
