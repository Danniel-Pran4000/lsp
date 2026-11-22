<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    protected $redirectRoute = 'rentals.preview';

    /**
     * Wajibkan ID pratinjau dan gambar bukti bayar sebelum sewa dapat disimpan.
     */
    public function rules(): array
    {
        return [
            'quote_id' => ['required', 'uuid'],
            'proof' => ['required', 'image', 'max:2048'],
        ];
    }
}
