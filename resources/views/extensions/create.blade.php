@extends('layouts.account')
@section('title', 'Perpanjang sewa')

@section('content')
    <h2>Sewa aktif hari ini</h2>
    <div class="table">
        <table>
            <thead>
                <tr><th>Unit</th><th>Penyewa</th><th>Tipe sewa</th><th>Tanggal selesai</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td>{{ $rental->garage->code }}</td>
                        <td>{{ $rental->user->name }}</td>
                        <td>{{ $rental->rental_type === 'monthly' ? 'Bulanan' : 'Harian' }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                        <td><a href="{{ route('extensions.preview', $rental) }}">Perpanjang {{ $rental->garage->code }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada sewa yang aktif hari ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

    <p><a href="{{ route('registration.pending') }}">Kembali ke verifikasi pendaftar</a></p>
@endsection
