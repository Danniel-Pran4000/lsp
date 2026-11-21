@extends('layouts.account')
@section('title', 'Masuk')

@section('content')
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <label>Email <input type="email" name="email" autocomplete="username" value="{{ old('email') }}" required></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Masuk</button>
    </form>
    <p><a href="{{ route('register') }}">Daftar sebagai penyewa</a></p>
@endsection
