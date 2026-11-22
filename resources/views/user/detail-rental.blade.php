@extends('layouts.header')
@section('title', 'Rincian sewa')

@section('content')
    @php
        $label = 'text-sm text-stone-600';
    @endphp

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-stone-200 p-5">
            <h2 class="text-lg font-semibold">Kendaraan dan periode</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="{{ $label }}">Penyewa</dt><dd>{{ $rental->user->name }}</dd></div>
                <div><dt class="{{ $label }}">Unit garasi</dt><dd>{{ $rental->garage->code }} · {{ $garageType }}</dd></div>
                <div><dt class="{{ $label }}">Kendaraan</dt><dd>{{ $rental->brand }} {{ $rental->vehicle_model }}</dd></div>
                <div><dt class="{{ $label }}">Kategori kendaraan</dt><dd>{{ $vehicle }}</dd></div>
                @if ($rental->plate)
                    <div><dt class="{{ $label }}">Nomor polisi</dt><dd>{{ $rental->plate }}</dd></div>
                @endif
                <div><dt class="{{ $label }}">Tipe sewa</dt><dd>{{ $duration }}</dd></div>
                <div><dt class="{{ $label }}">Durasi awal</dt><dd>{{ $rental->months }} bulan, {{ $rental->days }} hari</dd></div>
                <div><dt class="{{ $label }}">Tanggal mulai</dt><dd>{{ $rental->starts_on }}</dd></div>
                <div><dt class="{{ $label }}">Tanggal selesai awal</dt><dd>{{ $originalEnd }}</dd></div>
                <div><dt class="{{ $label }}">Tanggal selesai saat ini</dt><dd>{{ $rental->ends_on }}</dd></div>
                @if ($rental->battery_kwh !== null)
                    <div><dt class="{{ $label }}">Kapasitas baterai</dt><dd>{{ $rental->battery_kwh }} kWh</dd></div>
                    <div>
                        <dt class="{{ $label }}">Sisa baterai</dt>
                        <dd>
                            @if ($rental->remaining_percent !== null)
                                {{ $rental->remaining_percent }}%
                            @elseif ($rental->remaining_kwh !== null)
                                {{ $rental->remaining_kwh }} kWh
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-lg border border-stone-200 p-5">
            <h2 class="text-lg font-semibold">Pembayaran</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="{{ $label }}">Tarif harian</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->daily_rate) }}</dd></div>
                <div><dt class="{{ $label }}">Biaya sewa awal</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->rental_amount) }}</dd></div>
                @if ($rental->kwh_rate !== null)
                    <div><dt class="{{ $label }}">Tarif per kWh</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->kwh_rate) }}</dd></div>
                    <div><dt class="{{ $label }}">Estimasi energi pengisian</dt><dd>{{ $rental->energy_kwh }} kWh</dd></div>
                    <div><dt class="{{ $label }}">Biaya listrik</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->electricity_amount) }}</dd></div>
                @endif
                <div><dt class="{{ $label }}">Pembayaran awal</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->initial_total) }}</dd></div>
                <div><dt class="{{ $label }}">Total biaya perpanjangan</dt><dd>{{ \App\Support\Rupiah::formatAmount($rental->extensions_sum_amount ?? 0) }}</dd></div>
                <div class="col-span-2 rounded-lg bg-red-50 p-3">
                    <dt>Total pembayaran</dt>
                    <dd class="text-xl font-semibold text-red-800">{{ \App\Support\Rupiah::formatAmount($total) }}</dd>
                </div>
            </dl>
            <h3 class="mb-2 mt-5 text-sm font-semibold">Bukti pembayaran awal</h3>
            <a href="{{ route('rentals.proof', $rental) }}">
                <img class="max-h-64 w-auto rounded-lg border border-stone-200 object-contain"
                    src="{{ route('rentals.proof', $rental) }}" alt="Foto bukti pembayaran awal">
            </a>
        </section>
    </div>

    <h2 class="mb-3 mt-8 text-lg font-semibold">Riwayat perpanjangan</h2>
    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Tanggal perpanjangan</th>
                    <th scope="col" class="px-4 py-3">Akhir sebelumnya</th>
                    <th scope="col" class="px-4 py-3">Akhir baru</th>
                    <th scope="col" class="px-4 py-3">Biaya tambahan</th>
                    <th scope="col" class="px-4 py-3">Bukti pembayaran</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($extensions as $extension)
                    <tr>
                        <td class="px-4 py-3">{{ $extension->extended_on }}</td>
                        <td class="px-4 py-3">{{ $extension->old_end }}</td>
                        <td class="px-4 py-3">{{ $extension->new_end }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Rupiah::formatAmount($extension->amount) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('rentals.proof', [$rental, $extension]) }}">
                                <img class="h-20 w-auto rounded border border-stone-200 object-contain" loading="lazy"
                                    src="{{ route('rentals.proof', [$rental, $extension]) }}" alt="Foto bukti pembayaran perpanjangan">
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-8 text-center text-stone-600" colspan="5">Belum ada perpanjangan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensions->links() }}
    <a class="mt-6 inline-block text-sm font-medium text-red-700 hover:underline" href="{{ route($backRoute) }}">Kembali ke daftar sewa</a>
@endsection
