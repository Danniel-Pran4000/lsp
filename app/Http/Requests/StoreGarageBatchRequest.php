<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGarageBatchRequest extends FormRequest
{
    public function rules(): array
    {
        $rateRules = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];

        return [
            'type' => ['required', 'in:S,C,E'],
            'quantity' => ['required', 'integer', 'between:1,999'],
            'daily_rate' => $rateRules,
            'kwh_rate' => ['exclude_unless:type,E', ...$rateRules],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'garage type',
            'quantity' => 'number of units',
            'daily_rate' => 'daily rate',
            'kwh_rate' => 'rate per kWh',
        ];
    }
}
