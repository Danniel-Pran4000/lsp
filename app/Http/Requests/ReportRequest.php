<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    protected $redirectRoute = 'reports.index';

    public function rules(): array
    {
        return [
            'mode' => ['sometimes', 'required', 'in:monthly,daily,type,renter'],
            'year' => ['exclude_if:mode,type,renter', 'sometimes', 'required', 'integer', 'between:1000,9999'],
            'month' => ['exclude_unless:mode,daily', 'sometimes', 'required', 'integer', 'between:1,12'],
            'garage_type' => ['exclude_unless:mode,type', 'nullable', 'in:S,C,E'],
            'user_id' => ['exclude_unless:mode,renter', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'user')],
        ];
    }
}
