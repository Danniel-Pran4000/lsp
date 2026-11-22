@extends('layouts.header')
@section('title', 'Laporan')

@section('content')
    <form class="mb-5 flex flex-wrap items-end gap-3" action="{{ route('reports.index') }}" method="get">
        <label class="block text-sm font-medium">Jenis laporan
            <select name="mode" data-auto-filter class="mt-2 block rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm">
                @foreach (['monthly' => 'Bulanan', 'daily' => 'Harian', 'type' => 'Per tipe garasi', 'renter' => 'Per penyewa'] as $reportMode => $label)
                    <option value="{{ $reportMode }}" @selected($mode === $reportMode)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <noscript>
            <button type="submit" class="rounded-lg border px-3 py-2 text-sm">Pilih jenis laporan</button>
        </noscript>
    </form>

    {{-- Jenis laporan menentukan filter yang perlu diisi sebelum laporan ditampilkan. --}}
    <form action="{{ route('reports.index') }}" method="get" class="rounded-xl border border-stone-200 p-4 sm:p-5">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <div class="grid gap-4 sm:grid-cols-2">
            @if ($mode === 'monthly' || $mode === 'daily')
                <label class="block text-sm font-medium">Tahun
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="year" min="1000" max="9999" value="{{ $year }}" required>
                </label>
            @endif
            @if ($mode === 'daily')
                <label class="block text-sm font-medium">Bulan
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="month">
                        @foreach ($monthNames as $number => $name)
                            <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif ($mode === 'type')
                <label class="block text-sm font-medium">Tipe garasi
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="garage_type">
                        <option value="">Semua tipe</option>
                        @foreach ($garageTypes as $type => $label)
                            <option value="{{ $type }}" @selected($garageType === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif ($mode === 'renter')
                <label class="block text-sm font-medium">Penyewa
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="user_id">
                        <option value="">Semua penyewa</option>
                        @foreach ($renters as $renter)
                            <option value="{{ $renter->id }}" @selected($userId === $renter->id)>{{ $renter->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
        <div class="mt-4 flex justify-end">
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Tampilkan</button>
        </div>
    </form>

    <h2 class="mt-8 mb-4 text-lg font-semibold">Ringkasan</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">{{ match ($mode) {
                        'monthly' => 'Bulan',
                        'daily' => 'Tanggal',
                        'type' => 'Tipe garasi',
                        'renter' => 'Penyewa',
                    } }}</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Sewa baru</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Pembayaran awal</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Perpanjangan</th>
                    <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Pembayaran perpanjangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($groups as $key => $label)
                    <tr>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900" scope="row">{{ $label }}</th>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rentalTotals[$key]->transaction_count ?? 0 }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \App\Support\Rupiah::formatAmount($rentalTotals[$key]->total ?? 0) }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $extensionTotals[$key]->transaction_count ?? 0 }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \App\Support\Rupiah::formatAmount($extensionTotals[$key]->total ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="5">Tidak ada penyewa yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $groups->links() }}

    <h2 class="mt-8 mb-4 text-lg font-semibold">Rincian sewa</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead><tr><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">No.</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tanggal mulai</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tanggal selesai</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Unit</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Penyewa</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Pembayaran awal</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($rentalDetails as $rental)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rentalDetails->firstItem() + $loop->index }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->starts_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->ends_on }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \App\Support\Rupiah::formatAmount($rental->initial_total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="6">Tidak ada sewa yang sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentalDetails->links() }}

    <h2 class="mt-8 mb-4 text-lg font-semibold">Rincian pembayaran perpanjangan</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">No.</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tanggal perpanjangan</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Unit</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Penyewa</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Akhir sebelumnya</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Akhir baru</th>
                        <th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Pembayaran tambahan</th></tr>
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
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \App\Support\Rupiah::formatAmount($extension->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="7">Tidak ada pembayaran perpanjangan yang sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensionDetails->links() }}


@endsection
