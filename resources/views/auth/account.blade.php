@extends('layouts.account')
@section('title', 'Akun')

@section('content')
    <p>{{ $user->name }}</p>
    <p>{{ match ($user->approval_status) {
        'approved' => 'Pendaftaran diterima.',
        'rejected' => 'Pendaftaran ditolak.',
        default => 'Pendaftaran menunggu verifikasi admin.',
    } }}</p>
    @if ($user->canRent())
        <p><a href="{{ route('rentals.create') }}">Sewa baru</a></p>
    @endif
    @if ($user->role === 'user')
        <p><a href="{{ route('rentals.index') }}">Persewaan saya</a></p>
    @endif
@endsection
