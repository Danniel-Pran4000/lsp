@extends('layouts.account')
@section('title', 'Daftar penyewa')

@section('content')
    <p><a href="{{ route('garages.create') }}">Buat unit garasi</a></p>
    <p><a href="{{ route('extensions.create') }}">Perpanjang sewa</a></p>
    <p><a href="{{ route('reports.index') }}">Laporan persewaan</a></p>
    <p>Periksa KTP secara manual sebelum mengambil keputusan.</p>
    <div class="table">
        <table>
            <thead><tr><th>Nama</th><th>NIK</th><th>Telepon</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->nik }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ match ($user->approval_status) {
                            'approved' => 'Diterima',
                            'rejected' => 'Ditolak',
                            default => 'Menunggu verifikasi',
                        } }}</td>
                        <td>
                            <form method="post" action="{{ route('registration.verify', $user) }}">
                                @csrf
                                @method('PATCH')
                                @if ($user->approval_status !== 'approved')
                                    <button name="decision" value="approved">Terima</button>
                                @endif
                                @if ($user->approval_status !== 'rejected')
                                    <button name="decision" value="rejected">Tolak</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada akun penyewa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
