@extends('layouts.header')
@section('title', 'Daftar sewa')

@section('content')
    <div class="mb-5">
        @include('layouts.type-filter', ['filterRoute' => 'admin.rentals'])
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs font-semibold tracking-wide text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Unit</th>
                    <th scope="col" class="px-4 py-3">Penyewa</th>
                    <th scope="col" class="px-4 py-3">Tanggal mulai</th>
                    <th scope="col" class="px-4 py-3">Tanggal selesai</th>
                    <th scope="col" class="px-4 py-3">Rincian</th>
                    <th scope="col" class="px-4 py-3 text-right">Tindakan</th>
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
                                <summary class="cursor-pointer font-medium text-red-800">Lihat rincian</summary>
                                <dl class="mt-2 space-y-2 rounded-lg border border-stone-200 bg-white p-3 text-xs">
                                    <div>
                                        <dt class="text-stone-600">Kendaraan</dt>
                                        <dd class="font-semibold">{{ $rental->brand }} {{ $rental->vehicle_model }}</dd>
                                        <dd>{{ $vehicleTypes[$rental->vehicle_category] ?? $rental->vehicle_category }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-stone-600">Pembayaran awal</dt>
                                        <dd class="font-semibold tabular-nums">{{ \App\Support\Rupiah::format($rental->initial_total) }}</dd>
                                    </div>
                                </dl>
                            </details>
                        </td>
                        <td class="px-4 py-4 text-right align-top">
                            <a class="inline-block rounded-lg border border-red-200 px-3 py-2 text-sm font-medium whitespace-nowrap text-red-800 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('extensions.preview', $rental) }}">Perpanjang sewa</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-10 text-center text-stone-600" colspan="6">Tidak ada sewa yang ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($rentals->hasPages())
        <div class="mt-5">{{ $rentals->links() }}</div>
    @endif
@endsection
