@extends('layouts.account')
@section('title', 'Perpanjang sewa')

@section('content')
    <dl>
        <dt>Unit</dt>
        <dd>{{ $rental->garage->code }}</dd>
        <dt>Penyewa</dt>
        <dd>{{ $rental->user->name }}</dd>
        <dt>Tipe sewa awal</dt>
        <dd>{{ $rental->rental_type === 'monthly' ? 'Bulanan' : 'Harian' }}</dd>
        <dt>Tanggal selesai saat ini</dt>
        <dd>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</dd>
        <dt>Tarif harian sewa awal</dt>
        <dd>Rp {{ str_replace('.', ',', $rental->daily_rate) }}</dd>
    </dl>

    <form action="{{ route('extensions.preview', $rental) }}" method="get">
        <label>Tanggal selesai baru
            <input type="date" name="ends_on" min="{{ $earliestEnd }}" max="9999-12-31"
                value="{{ is_string(old('ends_on')) ? old('ends_on') : $newEnd }}" required>
        </label>
        <button type="submit">Hitung biaya tambahan</button>
    </form>

    @if ($charge)
        <dl>
            <dt>Tanggal selesai yang dikonfirmasi</dt>
            <dd>{{ \Carbon\CarbonImmutable::parse($newEnd)->format('d/m/Y') }}</dd>
            <dt>Lama perpanjangan</dt>
            <dd>{{ $charge['days'] }} hari</dd>
            <dt>Biaya tambahan</dt>
            <dd><strong>Rp {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['amount']) }}</strong></dd>
        </dl>
        <form action="{{ route('extensions.store', $rental) }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="quote_id" value="{{ $quoteId }}">
            <label>Bukti bayar tambahan (gambar, maksimal 2 MB)
                <input type="file" name="proof" accept="image/*" required>
            </label>
            <button type="submit">Simpan perpanjangan</button>
        </form>
    @endif

    <p><a href="{{ route('extensions.create') }}">Kembali ke daftar sewa aktif</a></p>
@endsection
