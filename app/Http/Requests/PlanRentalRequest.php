<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlanRentalRequest extends FormRequest
{
    protected $redirectRoute = 'rentals.create';

    /**
     * Sesuaikan input wajib dengan jenis kendaraan, durasi, dan tahap pencarian atau pratinjau.
     */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $vehicleCategory = ['required', 'in:bicycle,motorcycle,gas_car,electric_car'];
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

        if ($this->routeIs('rentals.available') && $this->expectsJson()) {
            // Dropdown unit dapat diperbarui sebelum penyewa selesai mengisi detail kendaraan.
            unset($rules['brand'], $rules['vehicle_model'], $rules['plate'], $rules['battery_kwh'], $rules['remaining_percent']);
        }

        if ($this->routeIs('rentals.preview')) {
            $rules['garage_id'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    /**
     * Terapkan batas harian menurut panjang bulan tanggal mulai setelah tanggal dan durasi lolos validasi dasar.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            // Lewati perhitungan batas jika tanggal atau durasi sudah gagal validasi dasar.
            if (! $this->isMethod('post')
                || $this->input('rental_type') !== 'daily'
                || $validator->errors()->has('starts_on') || $validator->errors()->has('days')) {
                return;
            }

            $start = CarbonImmutable::parse($this->input('starts_on') ?? 'today');
            $maximum = $start->daysInMonth - 1;
            if ((int) $this->input('days') > $maximum) {
                $validator->errors()->add('days', "Durasi sewa harian maksimal {$maximum} hari untuk bulan mulai yang dipilih.");
            }
        }];
    }
}
