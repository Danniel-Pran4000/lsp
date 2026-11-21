@extends('layouts.header')
@section('title', 'Review payment')

@section('content')
    <dl>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Unit code</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ $garage->code }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Rental period</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ $start->format('d/m/Y') }} to {{ $end->format('d/m/Y') }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Daily rate</dt>
        <dd class="mt-1 text-sm text-neutral-900">IDR {{ str_replace('.', ',', $charge['daily_rate']) }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Rental charge</dt>
        <dd class="mt-1 text-sm text-neutral-900">IDR {{ str_replace('.', ',', $charge['rental_amount']) }}</dd>
        @if ($charge['kwh_rate'] !== null)
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Rate per kWh</dt>
            <dd class="mt-1 text-sm text-neutral-900">IDR {{ str_replace('.', ',', $charge['kwh_rate']) }}</dd>
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Estimated energy</dt>
            <dd class="mt-1 text-sm text-neutral-900">{{ str_replace('.', ',', rtrim(rtrim($charge['energy_kwh'], '0'), '.')) }} kWh</dd>
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Estimated electricity charge</dt>
            <dd class="mt-1 text-sm text-neutral-900">IDR {{ str_replace('.', ',', rtrim(rtrim($charge['electricity_amount'], '0'), '.')) }}</dd>
        @endif
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Total payment</dt>
        <dd class="mt-1 text-sm text-neutral-900"><strong>IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['initial_total']) }}</strong></dd>
    </dl>

    <form method="post" action="{{ route('rentals.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="quote_id" value="{{ $quoteId }}">
        <label class="my-4 block text-sm font-medium text-neutral-700">Payment proof (image, maximum 2 MB)
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="file" name="proof" accept="image/*" required>
        </label>
        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Confirm rental</button>
    </form>

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('rentals.create') }}">Back to rental form</a></p>
@endsection
