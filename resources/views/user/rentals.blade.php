@extends('layouts.header')
@section('title', 'Sewa saya')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <p class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-sm font-medium text-red-800">
            <span aria-hidden="true" class="size-2 rounded-full bg-red-700"></span>
            Sewa aktif <strong>{{ $activeCount }}</strong>
        </p>
        @if ($canCreateRental)
            <a class="ml-auto rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('rentals.create') }}">Sewa baru</a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs font-semibold tracking-wide text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Unit garasi</th>
                    <th scope="col" class="px-4 py-3">Kendaraan</th>
                    <th scope="col" class="px-4 py-3">Tanggal mulai</th>
                    <th scope="col" class="px-4 py-3">Tanggal selesai</th>
                    <th scope="col" class="px-4 py-3 text-right">Total pembayaran</th>
                    <th scope="col" class="px-4 py-3 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($rentals as $rental)
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50">
                        <th scope="row" class="px-4 py-4 font-medium">
                            <span class="rounded-md bg-stone-100 px-2 py-1 text-xs font-semibold text-neutral-800">{{ $rental['unit'] }}</span>
                        </th>
                        <td class="px-4 py-4 font-medium text-neutral-900">{{ $rental['vehicle'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-stone-600">{{ $rental['start'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-stone-600">{{ $rental['end'] }}</td>
                        <td class="px-4 py-4 text-right font-semibold whitespace-nowrap text-neutral-900 tabular-nums">{{ $rental['total'] }}</td>
                        <td class="px-4 py-4 text-right">
                            <a class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium whitespace-nowrap text-red-800 hover:bg-red-50" href="{{ route('rentals.show', $rental['id']) }}">Lihat rincian</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-center text-stone-600" colspan="6">Belum ada sewa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rentals->hasPages())
        <div class="mt-5">{{ $rentals->links() }}</div>
    @endif
@endsection
