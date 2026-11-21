@extends('layouts.account')
@section('title', 'Extend rental')

@section('content')
    <h2>Active rentals</h2>
    <div class="table">
        <table>
            <thead>
                <tr><th>Unit</th><th>Renter</th><th>Rental type</th><th>End date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td>{{ $rental->garage->code }}</td>
                        <td>{{ $rental->user->name }}</td>
                        <td>{{ $rental->rental_type === 'monthly' ? 'Monthly' : 'Daily' }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                        <td><a href="{{ route('extensions.preview', $rental) }}">Perpanjang {{ $rental->garage->code }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No active rentals.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

    <p><a href="{{ route('registration.index') }}">Back to rentals</a></p>
@endsection
