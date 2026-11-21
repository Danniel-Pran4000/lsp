@extends('layouts.account')
@section('title', 'Reports')

@section('content')
    @php
        $formatRupiah = static fn ($amount) => 'IDR '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    @endphp

    <form action="{{ route('reports.index') }}" method="get">
        <label class="my-4 block text-sm font-medium text-neutral-700">Report mode
            <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="report-mode" name="mode">
                <option value="monthly" @selected($mode === 'monthly')>Monthly</option>
                <option value="daily" @selected($mode === 'daily')>Daily</option>
                <option value="type" @selected($mode === 'type')>By garage type</option>
                <option value="renter" @selected($mode === 'renter')>By renter</option>
            </select>
        </label>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" data-modes="monthly daily">
            <legend class="px-1 text-sm font-medium text-stone-600">Report period</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Year <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="year" min="1000" max="9999" value="{{ $year }}" required></label>
        </fieldset>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" data-modes="daily">
            <legend class="px-1 text-sm font-medium text-stone-600">Select month</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Month
                <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="month">
                    @foreach ($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" data-modes="type">
            <legend class="px-1 text-sm font-medium text-stone-600">Garage filter</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Garage type
                <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="garage_type">
                    <option value="">All types</option>
                    @foreach ($garageTypes as $type => $label)
                        <option value="{{ $type }}" @selected($garageType === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" data-modes="renter">
            <legend class="px-1 text-sm font-medium text-stone-600">Filter penyewa</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Renter
                <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="user_id">
                    <option value="">All renters</option>
                    @foreach ($renters as $renter)
                        <option value="{{ $renter->id }}" @selected($userId === $renter->id)>{{ $renter->name }} (#{{ $renter->id }})</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Show report</button>
    </form>

    <h2 class="mt-8 mb-4 text-lg font-semibold">Summary</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr>
                    <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">{{ match ($mode) {
                        'monthly' => 'Month',
                        'daily' => 'Date',
                        'type' => 'Garage type',
                        'renter' => 'Renter',
                    } }}</th>
                    <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">New rentals</th>
                    <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Initial payments</th>
                    <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Extensions</th>
                    <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Extension payments</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $key => $label)
                    <tr>
                        <th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500" scope="row">{{ $label }}@if ($mode === 'renter') (#{{ $key }})@endif</th>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rentalTotals[$key]->transaction_count ?? 0 }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $formatRupiah($rentalTotals[$key]->total ?? 0) }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extensionTotals[$key]->transaction_count ?? 0 }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $formatRupiah($extensionTotals[$key]->total ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="5">No renters found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 mb-4 text-lg font-semibold">Rental details</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead><tr><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Rental ID</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Start date</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">End date</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Unit</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Renter</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Initial payment</th></tr></thead>
            <tbody>
                @forelse ($rentalDetails as $rental)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">#{{ $rental->id }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->starts_on }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->ends_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $formatRupiah($rental->initial_total) }}</td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="6">No rentals found for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentalDetails->links() }}

    <h2 class="mt-8 mb-4 text-lg font-semibold">Extension payment details</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Rental ID</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Extension date</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Unit</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Renter</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Previous end</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">New end</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Additional payment</th></tr>
            </thead>
            <tbody>
                @forelse ($extensionDetails as $extension)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">#{{ $extension->rental_id }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->extended_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->rental->garage->code }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->old_end }}</td><td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->new_end }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $formatRupiah($extension->amount) }}</td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="7">No extension payments found for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensionDetails->links() }}

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('registration.index') }}">Back to rentals</a></p>

    
@endsection
