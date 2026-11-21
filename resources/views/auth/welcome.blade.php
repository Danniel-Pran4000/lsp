@extends('layouts.account')
@section('title', 'Welcome')
@section('content')
    <p class="my-3 text-sm leading-6">Manage your garage rentals in one place.</p>
    <p class="my-3 text-sm leading-6"><a class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" href="{{ route('login') }}">Sign in</a> <a class="text-red-700 hover:text-red-800" href="{{ route('register') }}">Create account</a></p>
@endsection
