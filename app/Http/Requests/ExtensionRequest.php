<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtensionRequest extends FormRequest
{
    /**
     * Validasi tanggal saat menghitung biaya serta bukti dan ID pratinjau saat mengonfirmasi.
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'quote_id' => ['required', 'uuid'],
                'proof' => ['required', 'image', 'max:2048'],
            ];
        }

        return ['ends_on' => ['sometimes', 'required', 'date_format:Y-m-d']];
    }

    /**
     * Kembalikan admin ke perpanjangan sewa yang sama saat validasi gagal agar tanggal pilihan dapat ditinjau kembali.
     */
    protected function getRedirectUrl(): string
    {
        $rental = $this->route('rental');
        $parameters = ['rental' => $rental];
        $quote = $this->session()->get('extension_quote');

        if ($this->isMethod('post') && ($quote['rental_id'] ?? null) === $rental->id) {
            $parameters['ends_on'] = $quote['new_end'];
        }

        return route('extensions.preview', $parameters);
    }
}
