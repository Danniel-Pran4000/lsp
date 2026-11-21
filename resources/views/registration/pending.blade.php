@extends('layouts.account')
@section('title', 'Verifikasi pendaftar')

@section('content')
    <p><a href="{{ route('garages.create') }}">Buat unit garasi</a></p>
    <p>Periksa KTP secara manual sebelum mengambil keputusan.</p>
    <div class="table">
        <table>
            <thead><tr><th>Nama</th><th>NIK</th><th>Telepon</th><th>Email</th><th>Keputusan</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->nik }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <form method="post" action="{{ route('registration.verify', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button name="decision" value="approved">Terima</button>
                                <button name="decision" value="rejected">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada pendaftar yang menunggu verifikasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
