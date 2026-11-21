<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlanRentalRequest extends FormRequest
{
    protected $redirectRoute = 'rentals.create';

    public function isUpdatingVehicleCategory(): bool
    {
        return $this->isMethod('post') && $this->routeIs('rentals.create')
            && $this->has('vehicle_selection');
    }

    public function isUpdatingRentalType(): bool
    {
        return $this->isMethod('post') && $this->routeIs('rentals.create')
            && $this->has('rental_type_selection');
    }

    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $vehicleCategory = ['required', 'in:bicycle,motorcycle,gas_car,electric_car'];
        if ($this->isUpdatingVehicleCategory()) {
            return ['vehicle_selection' => $vehicleCategory];
        }
        if ($this->isUpdatingRentalType()) {
            return ['rental_type_selection' => ['required', 'in:daily,monthly']];
        }

        // Batasi input bulanan ke ukuran yang dapat diproses dalam rentang kolom DATE.
        $days = $this->input('rental_type') === 'monthly'
            ? ['nullable', 'integer', 'between:0,3652058']
            : ['required', 'integer', 'between:1,30'];

        $rules = [
            'vehicle_category' => $vehicleCategory,
            'brand' => ['required', 'string', 'max:255'],
            'vehicle_model' => ['required', 'string', 'max:255'],
            'plate' => ['exclude_if:vehicle_category,bicycle', 'required', 'string', 'max:255'],
            'battery_kwh' => ['exclude_unless:vehicle_category,electric_car', 'required', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'remaining_percent' => ['exclude_unless:vehicle_category,electric_car', 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'starts_on' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:'.CarbonImmutable::today()->toDateString()],
            'rental_type' => ['required', 'in:daily,monthly'],
            'months' => ['exclude_unless:rental_type,monthly', 'required', 'integer', 'between:1,119987'],
            'days' => $days,
        ];

        if ($this->routeIs('rentals.available', 'rentals.preview')) {
            $rules['garage_type'] = ['required', 'in:S,C,E'];
        }

        if ($this->routeIs('rentals.preview')) {
            $rules['garage_id'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->isMethod('post') || $this->isUpdatingVehicleCategory() || $this->isUpdatingRentalType()
                || $this->input('rental_type') !== 'daily'
                || $validator->errors()->has('starts_on') || $validator->errors()->has('days')) {
                return;
            }

            $start = CarbonImmutable::parse($this->input('starts_on') ?? 'today');
            $maximum = $start->daysInMonth - 1;
            if ((int) $this->input('days') > $maximum) {
                $validator->errors()->add('days', "The maximum daily rental duration is {$maximum} days for the selected start month.");
            }
        }];
    }
}
