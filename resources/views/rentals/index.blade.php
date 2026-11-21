@extends('layouts.account')
@section('title', 'My rentals')

@section('content')
    <p>Account status: {{ ucfirst(auth()->user()->approval_status) }}</p>
    @if (auth()->user()->canRent())
        <p><a class="button" href="{{ route('rentals.create') }}">New rental</a></p>
    @else
        <p>Your account must be approved before you can create a rental.</p>
    @endif
    <p>{{ $rentals->total() }} rentals · {{ $garageCount }} distinct garage units</p>

    <div class="table">
        <table>
            <thead>
                <tr><th>Garage unit</th><th>Vehicle</th><th>Start date</th><th>End date</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr>
                        <td>{{ $rental->garage->code }}</td>
                        <td>{{ $rental->brand }} {{ $rental->vehicle_model }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->starts_on)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No rentals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

@endsection
