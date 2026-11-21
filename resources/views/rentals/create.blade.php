@extends('layouts.account')
@section('title', 'New rental')

@section('content')
    @php
        $vehicles = [
            'bicycle' => 'Bicycle',
            'motorcycle' => 'Motorcycle',
            'gas_car' => 'Petrol car',
            'electric_car' => 'Electric car',
        ];
        $garageLabels = ['S' => 'Bicycle (S)', 'C' => 'Petrol car (C)', 'E' => 'Electric car (E)'];
    @endphp

    <p>Planned start date <strong>{{ $start->format('d/m/Y') }}</strong></p>
    <form method="post" action="{{ route('rentals.create') }}">
        @csrf
        <label>Start date
            <input id="starts-on" type="date" name="starts_on" min="{{ today()->toDateString() }}" max="9999-12-31"
                value="{{ is_string(old('starts_on')) ? old('starts_on') : $start->toDateString() }}" required>
        </label>
        <label for="vehicle-category">Vehicle category</label>
        <select id="vehicle-category" name="vehicle_category" required>
            @foreach ($vehicles as $category => $label)
                <option value="{{ $category }}" @selected(old('vehicle_category', $details['vehicle_category'] ?? '') === $category)>{{ $label }}</option>
            @endforeach
        </select>
        <label>Brand
            <input name="brand" maxlength="255" value="{{ old('brand', $details['brand'] ?? '') }}" required>
        </label>
        <label>Vehicle model
            <input name="vehicle_model" maxlength="255" value="{{ old('vehicle_model', $details['vehicle_model'] ?? '') }}" required>
        </label>
        <fieldset id="plate-fields">
            <legend>For vehicles other than bicycles</legend>
            <label>License plate
                <input name="plate" maxlength="255" value="{{ old('plate', $details['plate'] ?? '') }}">
            </label>
        </fieldset>
        <fieldset id="battery-fields">
            <legend>For electric cars</legend>
            <label>Battery capacity (kWh)
                <input type="number" name="battery_kwh" min="0.001" step="0.001" value="{{ old('battery_kwh', $details['battery_kwh'] ?? '') }}">
            </label>
            <label>Remaining battery (%)
                <input type="number" name="remaining_percent" min="0" max="100" step="0.01" value="{{ old('remaining_percent', $details['remaining_percent'] ?? '') }}">
            </label>
        </fieldset>
        <label for="rental-type">Rental type</label>
        <select id="rental-type" name="rental_type" required>
            <option value="daily" @selected(old('rental_type', $details['rental_type'] ?? '') === 'daily')>Daily</option>
            <option value="monthly" @selected(old('rental_type', $details['rental_type'] ?? '') === 'monthly')>Monthly</option>
        </select>
        <fieldset id="monthly-fields">
            <legend>For monthly rentals</legend>
            <label>Number of months
                <input type="number" name="months" min="1" step="1" value="{{ old('months', $details['months'] ?? '') }}">
            </label>
        </fieldset>
        <label for="days" id="days-label">Days (additional days for monthly rentals)</label>
        <input id="days" type="number" name="days" min="0" step="1" value="{{ old('days', $details['days'] ?? '') }}" required>
        <p id="daily-limit">The daily limit depends on the start month.</p>
        <button type="submit">Calculate end date</button>
    </form>

    @if ($end)
        <section aria-label="Hasil perhitungan">
            <p>End date <strong>{{ $end->format('d/m/Y') }}</strong></p>
            <form method="post" action="{{ route('rentals.available') }}">
                @csrf
                @foreach ($details as $field => $value)
                    @continue($field === 'garage_type')
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label for="garage-type">Compatible garage type</label>
                <select id="garage-type" name="garage_type" required>
                    @foreach ($garageTypes as $type)
                        <option value="{{ $type }}" @selected(($selectedGarageType ?? null) === $type)>{{ $garageLabels[$type] }}</option>
                    @endforeach
                </select>
                <button type="submit">Find available units</button>
            </form>

            @isset($garages)
                @if ($garages->isEmpty())
                    <p role="status">No units of type {{ $garageLabels[$selectedGarageType] }} are available for this period.</p>
                @else
                    <form method="post" action="{{ route('rentals.preview') }}">
                        @csrf
                        @foreach ($details as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach
                        <label for="garage-id">Choose an available unit</label>
                        <select id="garage-id" name="garage_id" required>
                            <option value="">Select unit</option>
                            @foreach ($garages as $garage)
                                <option value="{{ $garage->id }}">{{ $garage->code }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Review payment</button>
                    </form>
                @endif
            @endisset
        </section>
    @endif

    <p><a href="{{ route('account.show') }}">Back to my rentals</a></p>

    
@endsection
