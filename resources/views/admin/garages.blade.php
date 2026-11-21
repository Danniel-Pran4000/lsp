@extends('layouts.header')
@section('title', 'Garage')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-stone-600">Available units today</p>
        @include('layouts.type-filter', ['filterRoute' => 'garages.index', 'filterLabel' => 'Garage type'])
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs font-semibold tracking-wide text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Unit</th>
                    <th scope="col" class="px-4 py-3">Garage type</th>
                    <th scope="col" class="px-4 py-3 text-right">Daily rate</th>
                    <th scope="col" class="px-4 py-3 text-right">Rate per kWh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($garages as $garage)
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50">
                        <th scope="row" class="px-4 py-4">
                            <span class="rounded-md bg-stone-100 px-2 py-1 text-xs font-semibold">{{ $garage->code }}</span>
                        </th>
                        <td class="px-4 py-4 text-stone-700">{{ $types[$garage->type] }}</td>
                        <td class="px-4 py-4 text-right text-stone-700 tabular-nums">IDR {{ number_format($garage->daily_rate, 2, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right text-stone-700 tabular-nums">
                            @if ($garage->kwh_rate !== null)
                                IDR {{ number_format($garage->kwh_rate, 2, ',', '.') }}
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-10 text-center text-stone-600" colspan="4">No available garage units found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($garages->hasPages())
        <div class="mt-5">{{ $garages->links() }}</div>
    @endif
@endsection
