@extends('layouts.header')
@section('title', 'My rentals')

@section('content')
    <p class="my-3 text-sm leading-6">Account status: {{ ucfirst(auth()->user()->approval_status) }}</p>
    @if (auth()->user()->canRent())
        <p class="my-3 text-sm leading-6"><a class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('rentals.create') }}">New rental</a></p>
    @else
        <p class="my-3 text-sm leading-6">Your account must be approved before you can create a rental.</p>
    @endif
    <p class="my-3 text-sm leading-6">{{ $rentals->total() }} rentals · {{ $garageCount }} distinct garage units</p>

    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Garage unit</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Vehicle</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Start date</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">End date</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->brand }} {{ $rental->vehicle_model }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \Carbon\CarbonImmutable::parse($rental->starts_on)->format('d/m/Y') }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="4">No rentals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

@endsection
