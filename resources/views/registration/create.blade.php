@extends('layouts.account')
@section('title', 'Daftar sebagai penyewa')

@section('content')
    <form method="post" action="{{ route('registration.store') }}">
        @csrf
        <label>NIK <input name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" value="{{ old('nik') }}" required></label>
        <label>Nama lengkap <input name="name" autocomplete="name" value="{{ old('name') }}" required></label>
        <label>Nomor telepon <input type="tel" name="phone" autocomplete="tel" value="{{ old('phone') }}" required></label>
        <label>Email <input type="email" name="email" autocomplete="email" value="{{ old('email') }}" required></label>
        <label>Password (minimal 8 karakter) <input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
        <button type="submit">Daftar</button>
    </form>
    <p><a href="{{ route('login') }}">Masuk</a></p>
@endsection
