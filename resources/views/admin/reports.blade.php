@extends('layouts.header')
@section('title', 'Reports')

@section('content')
    @php
        $formatRupiah = static fn ($amount) => 'IDR '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    @endphp

    <nav class="mb-5 flex flex-wrap gap-2" aria-label="Report mode">
        @foreach (['monthly' => 'Monthly', 'daily' => 'Daily', 'type' => 'By garage type', 'renter' => 'By renter'] as $reportMode => $label)
            <a href="{{ route('reports.index', ['mode' => $reportMode]) }}"
                @if ($mode === $reportMode) aria-current="page" @endif
                class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:border-red-400 aria-[current=page]:border-red-700 aria-[current=page]:bg-red-50 aria-[current=page]:text-red-800">{{ $label }}</a>
        @endforeach
    </nav>

    <form action="{{ route('reports.index') }}" method="get" class="rounded-xl border border-stone-200 p-4 sm:p-5">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <div class="grid gap-4 sm:grid-cols-2">
            @if ($mode === 'monthly' || $mode === 'daily')
                <label class="block text-sm font-medium">Year
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="year" min="1000" max="9999" value="{{ $year }}" required>
                </label>
            @endif
            @if ($mode === 'daily')
                <label class="block text-sm font-medium">Month
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="month">
                        @foreach ($monthNames as $number => $name)
                            <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif ($mode === 'type')
                <label class="block text-sm font-medium">Garage type
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="garage_type">
                        <option value="">All types</option>
                        @foreach ($garageTypes as $type => $label)
                            <option value="{{ $type }}" @selected($garageType === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif ($mode === 'renter')
                <label class="block text-sm font-medium">Renter
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="user_id">
                        <option value="">All renters</option>
                        @foreach ($renters as $renter)
                            <option value="{{ $renter->id }}" @selected($userId === $renter->id)>{{ $renter->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
        <div class="mt-4 flex justify-end">
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Show report</button>
        </div>
    </form>

    <h2 class="mt-8 mb-4 text-lg font-semibold">Summary</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">{{ match ($mode) {
                        'monthly' => 'Month',
                        'daily' => 'Date',
                        'type' => 'Garage type',
                        'renter' => 'Renter',
                    } }}</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">New rentals</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Initial payments</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Extensions</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Extension payments</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($groups as $key => $label)
                    <tr>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900" scope="row">{{ $label }}@if ($mode === 'renter') (#{{ $key }})@endif</th>
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
            <thead><tr><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">No.</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Start date</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">End date</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Unit</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Renter</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Initial payment</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($rentalDetails as $rental)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rentalDetails->firstItem() + $loop->index }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->starts_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->ends_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->user->name }}</td>
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
                <tr><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">No.</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Extension date</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Unit</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Renter</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Previous end</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">New end</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Additional payment</th></tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($extensionDetails as $extension)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extensionDetails->firstItem() + $loop->index }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->extended_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->old_end }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extension->new_end }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $formatRupiah($extension->amount) }}</td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="7">No extension payments found for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensionDetails->links() }}


    
@endsection
