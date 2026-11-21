@extends('layouts.header')
@section('title', 'Rentals')

@section('content')
    <div class="mb-5 flex justify-end">
        <a class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('garages.create') }}">Create garage units</a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs font-semibold tracking-wide text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Unit</th>
                    <th scope="col" class="px-4 py-3">Renter</th>
                    <th scope="col" class="px-4 py-3">Start date</th>
                    <th scope="col" class="px-4 py-3">End date</th>
                    <th scope="col" class="px-4 py-3">Details</th>
                    <th scope="col" class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($rentals as $rental)
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50">
                        <th scope="row" class="px-4 py-4">
                            <span class="rounded-md bg-stone-100 px-2 py-1 text-xs font-semibold">{{ $rental->garage->code }}</span>
                        </th>
                        <td class="px-4 py-4 text-stone-700">{{ $rental->user->name }}</td>
                        <td class="px-4 py-4 text-stone-700 whitespace-nowrap">{{ $rental->starts_on }}</td>
                        <td class="px-4 py-4 text-stone-700 whitespace-nowrap">{{ $rental->ends_on }}</td>
                        <td class="px-4 py-4 text-stone-700">
                            <details>
                                <summary class="cursor-pointer font-medium text-red-800">View details</summary>
                                <dl class="mt-2 space-y-2 rounded-lg border border-stone-200 bg-white p-3 text-xs">
                                    <div>
                                        <dt class="text-stone-600">Vehicle</dt>
                                        <dd class="font-semibold">{{ $rental->brand }} {{ $rental->vehicle_model }}</dd>
                                        <dd>{{ str_replace('_', ' ', $rental->vehicle_category) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-stone-600">Initial payment</dt>
                                        <dd class="font-semibold tabular-nums">IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $rental->initial_total) }}</dd>
                                    </div>
                                </dl>
                            </details>
                        </td>
                        <td class="px-4 py-4 text-right align-top">
                            <a class="inline-block rounded-lg border border-red-200 px-3 py-2 text-sm font-medium whitespace-nowrap text-red-800 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('extensions.preview', $rental) }}">Extend rental</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-10 text-center text-stone-600" colspan="6">No rentals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($rentals->hasPages())
        <div class="mt-5">{{ $rentals->links() }}</div>
    @endif
@endsection
