@extends('layouts.account')
@section('title', 'Persewaan saya')

@section('content')
    <p>{{ $rentals->total() }} persewaan · {{ $garageCount }} unit garasi berbeda</p>

    <div class="table">
        <table>
            <thead>
                <tr><th>Unit garasi</th><th>Kendaraan</th><th>Tanggal mulai</th><th>Tanggal selesai</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td>{{ $rental->garage->code }}</td>
                        <td>{{ $rental->brand }} {{ $rental->vehicle_model }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->starts_on)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada persewaan pada halaman ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

    <p><a href="{{ route('account.show') }}">Kembali ke akun</a></p>
@endsection
