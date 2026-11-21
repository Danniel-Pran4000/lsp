@extends('layouts.account')
@section('title', 'Welcome')
@section('content')
    <p>Manage your garage rentals in one place.</p>
    <p><a class="button" href="{{ route('login') }}">Sign in</a> <a href="{{ route('register') }}">Create account</a></p>
@endsection
