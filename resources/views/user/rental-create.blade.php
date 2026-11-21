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

    <p class="my-3 text-sm leading-6">Planned start date <strong>{{ $start->format('d/m/Y') }}</strong></p>
    <form method="post" action="{{ route('rentals.create') }}">
        @csrf
        <label class="my-4 block text-sm font-medium text-neutral-700">Start date
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="starts-on" type="date" name="starts_on" min="{{ today()->toDateString() }}" max="9999-12-31"
                value="{{ is_string(old('starts_on')) ? old('starts_on') : $start->toDateString() }}" required>
        </label>
        <label class="my-4 block text-sm font-medium text-neutral-700" for="vehicle-category">Vehicle category</label>
        <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="vehicle-category" name="vehicle_category" required>
            @foreach ($vehicles as $category => $label)
                <option value="{{ $category }}" @selected(old('vehicle_category', $details['vehicle_category'] ?? '') === $category)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="my-4 block text-sm font-medium text-neutral-700">Brand
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="brand" maxlength="255" value="{{ old('brand', $details['brand'] ?? '') }}" required>
        </label>
        <label class="my-4 block text-sm font-medium text-neutral-700">Vehicle model
            <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="vehicle_model" maxlength="255" value="{{ old('vehicle_model', $details['vehicle_model'] ?? '') }}" required>
        </label>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" id="plate-fields">
            <legend class="px-1 text-sm font-medium text-stone-600">For vehicles other than bicycles</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">License plate
                <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="plate" maxlength="255" value="{{ old('plate', $details['plate'] ?? '') }}">
            </label>
        </fieldset>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" id="battery-fields">
            <legend class="px-1 text-sm font-medium text-stone-600">For electric cars</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Battery capacity (kWh)
                <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="battery_kwh" min="0.001" step="0.001" value="{{ old('battery_kwh', $details['battery_kwh'] ?? '') }}">
            </label>
            <label class="my-4 block text-sm font-medium text-neutral-700">Remaining battery (%)
                <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="remaining_percent" min="0" max="100" step="0.01" value="{{ old('remaining_percent', $details['remaining_percent'] ?? '') }}">
            </label>
        </fieldset>
        <label class="my-4 block text-sm font-medium text-neutral-700" for="rental-type">Rental type</label>
        <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="rental-type" name="rental_type" required>
            <option value="daily" @selected(old('rental_type', $details['rental_type'] ?? '') === 'daily')>Daily</option>
            <option value="monthly" @selected(old('rental_type', $details['rental_type'] ?? '') === 'monthly')>Monthly</option>
        </select>
        <fieldset class="mt-4 rounded-lg border border-stone-200 p-4" id="monthly-fields">
            <legend class="px-1 text-sm font-medium text-stone-600">For monthly rentals</legend>
            <label class="my-4 block text-sm font-medium text-neutral-700">Number of months
                <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="months" min="1" step="1" value="{{ old('months', $details['months'] ?? '') }}">
            </label>
        </fieldset>
        <label class="my-4 block text-sm font-medium text-neutral-700" for="days" id="days-label">Days (additional days for monthly rentals)</label>
        <input class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="days" type="number" name="days" min="0" step="1" value="{{ old('days', $details['days'] ?? '') }}" required>
        <p class="my-3 text-sm leading-6" id="daily-limit">The daily limit depends on the start month.</p>
        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Calculate end date</button>
    </form>

    @if ($end)
        <section aria-label="Hasil perhitungan">
            <p class="my-3 text-sm leading-6">End date <strong>{{ $end->format('d/m/Y') }}</strong></p>
            <form method="post" action="{{ route('rentals.available') }}">
                @csrf
                @foreach ($details as $field => $value)
                    @continue($field === 'garage_type')
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label class="my-4 block text-sm font-medium text-neutral-700" for="garage-type">Compatible garage type</label>
                <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="garage-type" name="garage_type" required>
                    @foreach ($garageTypes as $type)
                        <option value="{{ $type }}" @selected(($selectedGarageType ?? null) === $type)>{{ $garageLabels[$type] }}</option>
                    @endforeach
                </select>
                <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Find available units</button>
            </form>

            @isset($garages)
                @if ($garages->isEmpty())
                    <p class="my-3 text-sm leading-6" role="status">No units of type {{ $garageLabels[$selectedGarageType] }} are available for this period.</p>
                @else
                    <form method="post" action="{{ route('rentals.preview') }}">
                        @csrf
                        @foreach ($details as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach
                        <label class="my-4 block text-sm font-medium text-neutral-700" for="garage-id">Choose an available unit</label>
                        <select class="mt-2 block w-full max-w-lg rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" id="garage-id" name="garage_id" required>
                            <option value="">Select unit</option>
                            @foreach ($garages as $garage)
                                <option value="{{ $garage->id }}">{{ $garage->code }}</option>
                            @endforeach
                        </select>
                        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Review payment</button>
                    </form>
                @endif
            @endisset
        </section>
    @endif

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('account.show') }}">Back to my rentals</a></p>

    
@endsection
