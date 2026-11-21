<?php

namespace App\Http\Requests;

use App\Models\Garage;
use Illuminate\Foundation\Http\FormRequest;

class StoreGarageBatchRequest extends FormRequest
{
    protected $redirectRoute = 'garages.create';

    public function rules(): array
    {
        $garageType = ['required', 'in:S,C,E'];
        $rateRules = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];

        return [
            'type' => $garageType,
            'quantity' => ['required', 'integer', 'between:1,'.Garage::MAX_UNITS_PER_TYPE],
            'daily_rate' => $rateRules,
            'kwh_rate' => ['exclude_unless:type,E', ...$rateRules],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipe garasi',
            'quantity' => 'jumlah unit',
            'daily_rate' => 'tarif harian',
            'kwh_rate' => 'tarif per kWh',
        ];
    }
}
