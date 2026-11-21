@extends('layouts.account')
@section('title', 'Sign in')

@section('content')
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <label>Email <input type="email" name="email" autocomplete="username" value="{{ old('email') }}" required></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Sign in</button>
    </form>
    <p><a href="{{ route('register') }}">Create account</a></p>
@endsection
