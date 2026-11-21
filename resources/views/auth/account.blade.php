@extends('layouts.account')
@section('title', 'Akun')

@section('content')
    <p>{{ $user->name }}</p>
    <p>{{ match ($user->approval_status) {
        'approved' => 'Registration approved.',
        'rejected' => 'Registration rejected.',
        default => 'Registration pending approval.',
    } }}</p>
    @if ($user->canRent())
        <p><a href="{{ route('rentals.create') }}">New rental</a></p>
    @endif
    @if ($user->role === 'user')
        <p><a href="{{ route('rentals.index') }}">My rentals</a></p>
    @endif
@endsection
