@extends('layouts.account')
@section('title', 'Create garage units')

@section('content')
    @if (session('garage_codes'))
        <p>Created unit codes</p>
        <p>{{ implode(', ', session('garage_codes')) }}</p>
    @endif

    <form method="post" action="{{ route('garages.store-batch') }}">
        @csrf
        <label for="type">Garage type</label>
        <select id="type" name="type" required>
            <option value="S" @selected(old('type') === 'S')>Bicycle</option>
            <option value="C" @selected(old('type') === 'C')>Petrol car</option>
            <option value="E" @selected(old('type') === 'E')>Electric car</option>
        </select>
        <label>Number of units
            <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity') }}" required>
        </label>
        <label>Daily rate (Rp)
            <input type="number" name="daily_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('daily_rate') }}" required>
        </label>
        <label id="kwh-rate-field">Rate per kWh (IDR, electric garages only)
            <input type="number" name="kwh_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('kwh_rate') }}">
        </label>
        <button type="submit">Create units</button>
    </form>
    <p><a href="{{ route('registration.index') }}">Users</a></p>

    
@endsection
