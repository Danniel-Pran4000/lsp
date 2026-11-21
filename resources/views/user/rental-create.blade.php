@extends('layouts.header')
@section('title', 'New rental')

@section('content')
    <form method="post" action="{{ route('rentals.create') }}">
        @csrf
        <input type="hidden" name="vehicle_category" value="{{ $vehicleCategory }}">
        <input type="hidden" name="rental_type" value="{{ $rentalType }}">
        {{-- Keep calculation as the default action when Enter is pressed in a field. --}}
        <button type="submit" hidden>Calculate end date</button>

        <div class="grid gap-6 lg:grid-cols-2">
            <fieldset class="min-w-0 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold text-neutral-900">Vehicle details</legend>
                <div class="space-y-4">
                    <fieldset>
                        <legend class="text-sm font-medium text-neutral-800">Vehicle category</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($vehicles as $category => $label)
                                <button type="submit" name="vehicle_selection" value="{{ $category }}" formnovalidate
                                    aria-pressed="{{ $vehicleCategory === $category ? 'true' : 'false' }}"
                                    class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:border-red-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700 aria-pressed:border-red-700 aria-pressed:bg-red-50 aria-pressed:text-red-800">{{ $label }}</button>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium text-neutral-800">Brand
                            <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="brand" maxlength="255" value="{{ old('brand', $details['brand'] ?? '') }}" required>
                        </label>
                        <label class="block text-sm font-medium text-neutral-800">Vehicle model
                            <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="vehicle_model" maxlength="255" value="{{ old('vehicle_model', $details['vehicle_model'] ?? '') }}" required>
                        </label>
                    </div>

                    <label class="block text-sm font-medium text-neutral-800">License plate
                        <span class="block text-xs text-stone-600">Required for vehicles other than bicycles.</span>
                        <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" name="plate" maxlength="255"
                            value="{{ $needsPlate ? old('plate', $details['plate'] ?? '') : '' }}" @disabled(! $needsPlate)>
                    </label>

                    <fieldset class="min-w-0 border-t border-stone-200 pt-3" @disabled(! $needsBattery)>
                        <legend class="pr-2 text-xs font-semibold text-stone-600">Electric cars only</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-neutral-800">Battery capacity (kWh)
                                <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="battery_kwh" min="0.001" step="0.001"
                                    value="{{ $needsBattery ? old('battery_kwh', $details['battery_kwh'] ?? '') : '' }}">
                            </label>
                            <label class="block text-sm font-medium text-neutral-800">Remaining battery (%)
                                <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="remaining_percent" min="0" max="100" step="0.01"
                                    value="{{ $needsBattery ? old('remaining_percent', $details['remaining_percent'] ?? '') : '' }}">
                            </label>
                        </div>
                    </fieldset>
                </div>
            </fieldset>

            <fieldset class="min-w-0 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold text-neutral-900">Rental period</legend>
                <div class="space-y-4">
                    <label class="block text-sm font-medium text-neutral-800">Start date
                        <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="date" name="starts_on" min="{{ today()->toDateString() }}" max="9999-12-31"
                            value="{{ is_string(old('starts_on')) ? old('starts_on') : $start->toDateString() }}" required>
                    </label>

                    <fieldset>
                        <legend class="text-sm font-medium text-neutral-800">Rental type</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (['daily' => 'Daily', 'monthly' => 'Monthly'] as $type => $label)
                                <button type="submit" name="rental_type_selection" value="{{ $type }}" formnovalidate
                                    aria-pressed="{{ $rentalType === $type ? 'true' : 'false' }}"
                                    class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:border-red-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700 aria-pressed:border-red-700 aria-pressed:bg-red-50 aria-pressed:text-red-800">{{ $label }}</button>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium text-neutral-800">Months
                            <span class="block text-xs text-stone-600">For monthly rentals only.</span>
                            <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="months" min="1" step="1"
                                value="{{ $isMonthly ? old('months', $details['months'] ?? '') : '' }}" @disabled(! $isMonthly) @required($isMonthly)>
                        </label>
                        <label class="block text-sm font-medium text-neutral-800">{{ $isMonthly ? 'Extra days' : 'Days' }}
                            <span class="block text-xs text-stone-600">{{ $isMonthly ? 'Optional. Leave blank for whole months.' : 'The limit depends on the start month.' }}</span>
                            <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" type="number" name="days"
                                min="{{ $isMonthly ? 0 : 1 }}" step="1" value="{{ old('days', $details['days'] ?? '') }}" @required(! $isMonthly)>
                        </label>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('rentals.index') }}">Back to My rentals</a>
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Calculate end date</button>
        </div>
    </form>

    @if ($end)
        <section class="mt-8 rounded-xl border border-red-100 bg-red-50/40 p-4 sm:p-5" aria-labelledby="rental-availability">
            <h2 id="rental-availability" class="text-lg font-semibold">Find a garage</h2>
            <p class="mt-1 text-sm text-stone-700">End date <strong>{{ $end->format('d/m/Y') }}</strong></p>

            <form class="mt-5 flex flex-wrap items-end gap-4" method="post" action="{{ route('rentals.available') }}">
                @csrf
                @foreach ($details as $field => $value)
                    @continue($field === 'garage_type')
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label class="block min-w-48 text-sm font-medium text-neutral-800">Compatible garage type
                    <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="garage_type" required>
                        @foreach ($garageTypes as $type)
                            <option value="{{ $type }}" @selected(($selectedGarageType ?? null) === $type)>{{ $garageLabels[$type] }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Find available units</button>
            </form>

            @isset($garages)
                @if ($garages->isEmpty())
                    <p class="mt-5 text-sm" role="status">No units of type {{ $garageLabels[$selectedGarageType] }} are available for this period.</p>
                @else
                    <form class="mt-5 flex flex-wrap items-end gap-4 border-t border-red-100 pt-5" method="post" action="{{ route('rentals.preview') }}">
                        @csrf
                        @foreach ($details as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach
                        <label class="block min-w-48 text-sm font-medium text-neutral-800">Choose an available unit
                            <select class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700" name="garage_id" required>
                                <option value="">Select unit</option>
                                @foreach ($garages as $garage)
                                    <option value="{{ $garage->id }}">{{ $garage->code }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Review payment</button>
                    </form>
                @endif
            @endisset
        </section>
    @endif

@endsection
