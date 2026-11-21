@extends('layouts.header')
@section('title', 'Extend rental')

@section('content')
    <dl>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Unit</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ $rental->garage->code }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Renter</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ $rental->user->name }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Rental type awal</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ $rental->rental_type === 'monthly' ? 'Monthly' : 'Daily' }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Current end date</dt>
        <dd class="mt-1 text-sm text-neutral-900">{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</dd>
        <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Original daily rate</dt>
        <dd class="mt-1 text-sm text-neutral-900">IDR {{ str_replace('.', ',', $rental->daily_rate) }}</dd>
    </dl>

    <form action="{{ route('extensions.preview', $rental) }}" method="get">
        <label class="my-4 block text-sm font-medium text-neutral-700">New end date
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="date" name="ends_on" min="{{ $earliestEnd }}" max="9999-12-31"
                value="{{ is_string(old('ends_on')) ? old('ends_on') : $newEnd }}" required>
        </label>
        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Calculate additional charge</button>
    </form>

    @if ($charge)
        <dl>
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">End date yang dikonfirmasi</dt>
            <dd class="mt-1 text-sm text-neutral-900">{{ \Carbon\CarbonImmutable::parse($newEnd)->format('d/m/Y') }}</dd>
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Extension length</dt>
            <dd class="mt-1 text-sm text-neutral-900">{{ $charge['days'] }} days</dd>
            <dt class="mt-3 text-xs font-medium uppercase tracking-wide text-stone-500">Additional charge</dt>
            <dd class="mt-1 text-sm text-neutral-900"><strong>IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['amount']) }}</strong></dd>
        </dl>
        <form action="{{ route('extensions.store', $rental) }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="quote_id" value="{{ $quoteId }}">
            <label class="my-4 block text-sm font-medium text-neutral-700">Payment proof (image, maximum 2 MB)
                <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="file" name="proof" accept="image/*" required>
            </label>
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Save extension</button>
        </form>
    @endif

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('extensions.create') }}">Back to rentals</a></p>
@endsection
