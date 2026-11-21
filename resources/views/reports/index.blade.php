@extends('layouts.account')
@section('title', 'Reports')

@section('content')
    @php
        $formatRupiah = static fn ($amount) => 'IDR '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    @endphp

    <form action="{{ route('reports.index') }}" method="get">
        <label>Report mode
            <select id="report-mode" name="mode">
                <option value="monthly" @selected($mode === 'monthly')>Monthly</option>
                <option value="daily" @selected($mode === 'daily')>Daily</option>
                <option value="type" @selected($mode === 'type')>By garage type</option>
                <option value="renter" @selected($mode === 'renter')>By renter</option>
            </select>
        </label>
        <fieldset data-modes="monthly daily">
            <legend>Report period</legend>
            <label>Year <input type="number" name="year" min="1000" max="9999" value="{{ $year }}" required></label>
        </fieldset>
        <fieldset data-modes="daily">
            <legend>Select month</legend>
            <label>Month
                <select name="month">
                    @foreach ($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset data-modes="type">
            <legend>Garage filter</legend>
            <label>Garage type
                <select name="garage_type">
                    <option value="">All types</option>
                    @foreach ($garageTypes as $type => $label)
                        <option value="{{ $type }}" @selected($garageType === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <fieldset data-modes="renter">
            <legend>Filter penyewa</legend>
            <label>Renter
                <select name="user_id">
                    <option value="">All renters</option>
                    @foreach ($renters as $renter)
                        <option value="{{ $renter->id }}" @selected($userId === $renter->id)>{{ $renter->name }} (#{{ $renter->id }})</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
        <button type="submit">Show report</button>
    </form>

    <h2>Summary</h2>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>{{ match ($mode) {
                        'monthly' => 'Month',
                        'daily' => 'Date',
                        'type' => 'Garage type',
                        'renter' => 'Renter',
                    } }}</th>
                    <th>New rentals</th>
                    <th>Initial payments</th>
                    <th>Extensions</th>
                    <th>Extension payments</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $key => $label)
                    <tr>
                        <th scope="row">{{ $label }}@if ($mode === 'renter') (#{{ $key }})@endif</th>
                        <td>{{ $rentalTotals[$key]->transaction_count ?? 0 }}</td>
                        <td>{{ $formatRupiah($rentalTotals[$key]->total ?? 0) }}</td>
                        <td>{{ $extensionTotals[$key]->transaction_count ?? 0 }}</td>
                        <td>{{ $formatRupiah($extensionTotals[$key]->total ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No renters found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Rental details</h2>
    <div class="table">
        <table>
            <thead><tr><th>Rental ID</th><th>Start date</th><th>End date</th><th>Unit</th><th>Renter</th><th>Initial payment</th></tr></thead>
            <tbody>
                @forelse ($rentalDetails as $rental)
                    <tr>
                        <td>#{{ $rental->id }}</td><td>{{ $rental->starts_on }}</td><td>{{ $rental->ends_on }}</td>
                        <td>{{ $rental->garage->code }}</td><td>{{ $rental->user->name }}</td>
                        <td>{{ $formatRupiah($rental->initial_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No rentals found for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentalDetails->links() }}

    <h2>Extension payment details</h2>
    <div class="table">
        <table>
            <thead>
                <tr><th>Rental ID</th><th>Extension date</th><th>Unit</th><th>Renter</th><th>Previous end</th><th>New end</th><th>Additional payment</th></tr>
            </thead>
            <tbody>
                @forelse ($extensionDetails as $extension)
                    <tr>
                        <td>#{{ $extension->rental_id }}</td><td>{{ $extension->extended_on }}</td>
                        <td>{{ $extension->rental->garage->code }}</td><td>{{ $extension->rental->user->name }}</td>
                        <td>{{ $extension->old_end }}</td><td>{{ $extension->new_end }}</td>
                        <td>{{ $formatRupiah($extension->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No extension payments found for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $extensionDetails->links() }}

    <p><a href="{{ route('registration.index') }}">Back to rentals</a></p>

    
@endsection
