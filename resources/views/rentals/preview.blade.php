@extends('layouts.account')
@section('title', 'Konfirmasi sewa')

@section('content')
    <dl>
        <dt>Kode unit</dt>
        <dd>{{ $garage->code }}</dd>
        <dt>Periode sewa</dt>
        <dd>{{ $start->format('d/m/Y') }} sampai {{ $end->format('d/m/Y') }}</dd>
        <dt>Tarif harian</dt>
        <dd>Rp {{ str_replace('.', ',', $charge['daily_rate']) }}</dd>
        <dt>Biaya sewa</dt>
        <dd>Rp {{ str_replace('.', ',', $charge['rental_amount']) }}</dd>
        @if ($charge['kwh_rate'] !== null)
            <dt>Tarif per kWh</dt>
            <dd>Rp {{ str_replace('.', ',', $charge['kwh_rate']) }}</dd>
            <dt>Estimasi energi pengisian</dt>
            <dd>{{ str_replace('.', ',', rtrim(rtrim($charge['energy_kwh'], '0'), '.')) }} kWh</dd>
            <dt>Estimasi biaya listrik</dt>
            <dd>Rp {{ str_replace('.', ',', rtrim(rtrim($charge['electricity_amount'], '0'), '.')) }}</dd>
        @endif
        <dt>Total pelunasan</dt>
        <dd><strong>Rp {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['initial_total']) }}</strong></dd>
    </dl>

    <form method="post" action="{{ route('rentals.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="quote_id" value="{{ $quoteId }}">
        <label>Bukti pelunasan (gambar, maksimal 2 MB)
            <input type="file" name="proof" accept="image/*" required>
        </label>
        <button type="submit">Simpan sewa</button>
    </form>

    <p><a href="{{ route('rentals.create') }}">Kembali ke formulir sewa</a></p>
@endsection
