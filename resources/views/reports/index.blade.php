@extends('layouts.account')
@section('title', 'Laporan persewaan')

@section('content')
    @php
        $formatRupiah = static fn ($amount) => 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    @endphp

    <form action="{{ route('reports.index') }}" method="get">
        <label>Mode laporan
            <select id="report-mode" name="mode">
                <option value="monthly" @selected($mode === 'monthly')>Per bulan</option>
                <option value="daily" @selected($mode === 'daily')>Per hari</option>
                <option value="type" @selected($mode === 'type')>Per tipe garasi</option>
                <option value="renter" @selected($mode === 'renter')>Per penyewa</option>
            </select>
        </label>
        <fieldset data-modes="monthly daily">
            <legend>Periode laporan</legend>
            <label>Tahun <input type="number" name="year" min="1000" max="9999" value="{{ $year }}" required></label>
        </fieldset>
        <fieldset data-modes="daily">
            <legend>Pilih bulan</legend>
            <label>Bulan
                <select name="month">
                    @foreach ($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset data-modes="type">
            <legend>Filter garasi</legend>
            <label>Tipe garasi
                <select name="garage_type">
                    <option value="">Semua tipe</option>
                    @foreach ($garageTypes as $type => $label)
                        <option value="{{ $type }}" @selected($garageType === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset data-modes="renter">
            <legend>Filter penyewa</legend>
            <label>Penyewa
                <select name="user_id">
                    <option value="">Semua penyewa</option>
                    @foreach ($renters as $renter)
                        <option value="{{ $renter->id }}" @selected($userId === $renter->id)>{{ $renter->name }} (#{{ $renter->id }})</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <button type="submit">Tampilkan</button>
    </form>

    <h2>Ringkasan</h2>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>{{ match ($mode) {
                        'monthly' => 'Bulan',
                        'daily' => 'Tanggal',
                        'type' => 'Tipe garasi',
                        'renter' => 'Penyewa',
                    } }}</th>
                    <th>Jumlah sewa baru</th>
                    <th>Total biaya awal</th>
                    <th>Jumlah perpanjangan</th>
                    <th>Total pembayaran perpanjangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $key => $label)
                    <tr>
                        <th scope="row">{{ $label }}@if ($mode === 'renter') (#{{ $key }})@endif</th>
                        <td>{{ $rentalTotals[$key]->transaction_count ?? 0 }}</td>
                        <td>{{ $formatRupiah($rentalTotals[$key]->total ?? 0) }}</td>
                        <td>{{ $extensionTotals[$key]->transaction_count ?? 0 }}</td>
                        <td>{{ $formatRupiah($extensionTotals[$key]->total ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada penyewa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Detail sewa baru</h2>
    <div class="table">
        <table>
            <thead><tr><th>No. sewa</th><th>Tanggal mulai</th><th>Tanggal selesai</th><th>Unit</th><th>Penyewa</th><th>Total awal</th></tr></thead>
            <tbody>
                @forelse ($rentalDetails as $rental)
                    <tr>
                        <td>#{{ $rental->id }}</td><td>{{ $rental->starts_on }}</td><td>{{ $rental->ends_on }}</td>
                        <td>{{ $rental->garage->code }}</td><td>{{ $rental->user->name }}</td>
                        <td>{{ $formatRupiah($rental->initial_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada sewa baru untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentalDetails->links() }}

    <h2>Detail pembayaran perpanjangan</h2>
    <div class="table">
        <table>
            <thead>
                <tr><th>No. sewa</th><th>Tanggal perpanjangan</th><th>Unit</th><th>Penyewa</th><th>Akhir lama</th><th>Akhir baru</th><th>Tambahan bayar</th></tr>
            </thead>
            <tbody>
                @forelse ($extensionDetails as $extension)
                    <tr>
                        <td>#{{ $extension->rental_id }}</td><td>{{ $extension->extended_on }}</td>
                        <td>{{ $extension->rental->garage->code }}</td><td>{{ $extension->rental->user->name }}</td>
                        <td>{{ $extension->old_end }}</td><td>{{ $extension->new_end }}</td>
                        <td>{{ $formatRupiah($extension->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Tidak ada pembayaran perpanjangan untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensionDetails->links() }}

    <p><a href="{{ route('registration.index') }}">Kembali ke daftar penyewa</a></p>

    <script>
        const reportMode = document.querySelector('#report-mode');
        const reportFields = document.querySelectorAll('[data-modes]');

        function updateReportFilters() {
            reportFields.forEach((field) => {
                const applies = field.dataset.modes.split(' ').includes(reportMode.value);
                field.hidden = !applies;
                field.disabled = !applies;
            });
        }

        reportMode.addEventListener('change', updateReportFilters);
        updateReportFilters();
    </script>
@endsection
