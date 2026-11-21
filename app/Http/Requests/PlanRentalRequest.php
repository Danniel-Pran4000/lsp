<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRentalRequest extends FormRequest
{
    protected $redirectRoute = 'rentals.create';

    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        // Batasi input bulanan ke ukuran yang dapat diproses dalam rentang kolom DATE.
        $days = $this->input('rental_type') === 'monthly'
            ? 'between:0,3652058'
            : 'between:1,'.(today()->daysInMonth - 1);

        $rules = [
            'vehicle_category' => ['required', 'in:bicycle,motorcycle,gas_car,electric_car'],
            'brand' => ['required', 'string', 'max:255'],
            'vehicle_model' => ['required', 'string', 'max:255'],
            'plate' => ['exclude_if:vehicle_category,bicycle', 'required', 'string', 'max:255'],
            'battery_kwh' => ['exclude_unless:vehicle_category,electric_car', 'required', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'remaining_percent' => ['exclude_unless:vehicle_category,electric_car', 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'rental_type' => ['required', 'in:daily,monthly'],
            'months' => ['exclude_unless:rental_type,monthly', 'required', 'integer', 'between:1,119987'],
            'days' => ['required', 'integer', $days],
        ];

        if ($this->routeIs('rentals.available')) {
            $rules['garage_type'] = ['required', 'in:S,C,E'];
        }

        return $rules;
    }
}
