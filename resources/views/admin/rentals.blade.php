@extends('layouts.account')
@section('title', 'Rentals')
@section('content')
<div class="page-heading"><div><h1>Rentals</h1><p>View and manage rental records.</p></div><a class="button" href="{{ route('garages.create') }}">Create garage units</a></div>
<div class="panel"><div class="table"><table><thead><tr><th>Unit</th><th>Renter</th><th>Start date</th><th>End date</th><th>Actions</th></tr></thead><tbody>
@forelse ($rentals as $rental)
<tr><td>{{ $rental->garage->code }}</td><td>{{ $rental->user->name }}</td><td>{{ $rental->starts_on }}</td><td>{{ $rental->ends_on }}</td><td><details><summary>View details</summary><p>{{ $rental->brand }} {{ $rental->vehicle_model }} · {{ $rental->vehicle_category }}</p><p>Initial payment: IDR {{ number_format($rental->initial_total, 0, ',', '.') }}</p></details>@if ($rental->isActiveOn(\Carbon\CarbonImmutable::today()))<a href="{{ route('extensions.preview', $rental) }}">Extend rental</a>@endif</td></tr>
@empty <tr><td colspan="5">No rentals found.</td></tr> @endforelse
</tbody></table></div>{{ $rentals->links() }}</div>
@endsection
