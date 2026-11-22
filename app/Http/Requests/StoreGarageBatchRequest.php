<?php

namespace App\Http\Requests;

use App\Models\Garage;
use Illuminate\Foundation\Http\FormRequest;

class StoreGarageBatchRequest extends FormRequest
{
    protected $redirectRoute = 'garages.create';

    /**
     * Periksa jumlah unit dan tarif per tipe, termasuk tarif listrik khusus garasi listrik.
     */
    public function rules(): array
    {
        $garageType = ['required', 'in:S,C,E'];
        $rateRules = ['required', 'integer', 'min:0', 'max:999999999999'];

        return [
            'type' => $garageType,
            'quantity' => ['required', 'integer', 'between:1,'.Garage::MAX_UNITS_PER_TYPE],
            'daily_rate' => $rateRules,
            'kwh_rate' => ['exclude_unless:type,E', ...$rateRules],
        ];
    }

    /**
     * Gunakan nama kolom dalam bahasa Indonesia agar pesan kesalahan mudah dipahami admin.
     */
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
