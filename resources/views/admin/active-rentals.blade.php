@extends('layouts.account')
@section('title', 'Extend rental')

@section('content')
    <h2 class="mt-8 mb-4 text-lg font-semibold">Active rentals</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Unit</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Renter</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Rental type</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">End date</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->rental_type === 'monthly' ? 'Monthly' : 'Daily' }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700"><a class="text-red-700 hover:text-red-800" href="{{ route('extensions.preview', $rental) }}">Perpanjang {{ $rental->garage->code }}</a></td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="5">No active rentals.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('registration.index') }}">Back to rentals</a></p>
@endsection
