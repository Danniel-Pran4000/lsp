@extends('layouts.header')
@section('title', 'Akun')

@section('content')
    <p class="my-3 text-sm leading-6">{{ $user->name }}</p>
    <p class="my-3 text-sm leading-6">{{ match ($user->approval_status) {
        'approved' => 'Registration approved.',
        'rejected' => 'Registration rejected.',
        default => 'Registration pending approval.',
    } }}</p>
    @if ($user->canRent())
        <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('rentals.create') }}">New rental</a></p>
    @endif
    @if ($user->role === 'user')
        <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('rentals.index') }}">My rentals</a></p>
    @endif
@endsection
