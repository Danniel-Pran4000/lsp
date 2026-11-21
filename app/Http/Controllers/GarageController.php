<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function create(): View
    {
        return view('garages.create');
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $rateRules = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];
        $data = $request->validate([
            'type' => ['required', 'in:S,C,E'],
            'quantity' => ['required', 'integer', 'between:1,999'],
            'daily_rate' => $rateRules,
            'kwh_rate' => ['exclude_unless:type,E', ...$rateRules],
        ], attributes: [
            'type' => 'tipe garasi',
            'quantity' => 'jumlah unit',
            'daily_rate' => 'tarif harian',
            'kwh_rate' => 'tarif per kWh',
        ]);

        $type = $data['type'];
        $quantity = (int) $data['quantity'];
        // Kode berawalan sama dengan tiga digit dapat diurutkan sebagai teks.
        $garagesOfType = Garage::whereBetween('code', [$type.'001', $type.'999']);

        try {
            $codes = DB::transaction(function () use ($garagesOfType, $type, $quantity, $data): array {
                $lastNumber = (int) substr($garagesOfType->max('code') ?? '', 1);
                $remaining = 999 - $lastNumber;

                if ($quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'quantity' => "Kapasitas kode tipe {$type} tersisa {$remaining} unit.",
                    ]);
                }

                $units = [];
                for ($number = $lastNumber + 1; $number <= $lastNumber + $quantity; $number++) {
                    $units[] = [
                        'code' => sprintf('%s%03d', $type, $number),
                        'type' => $type,
                        'daily_rate' => $data['daily_rate'],
                        'kwh_rate' => $data['kwh_rate'] ?? null,
                    ];
                }

                Garage::query()->fillAndInsert($units);

                return array_column($units, 'code');
            }, 3);
        } catch (UniqueConstraintViolationException) {
            // Transaksi sudah dibatalkan. Baca kapasitas setelah batch lain tersimpan.
            $remaining = 999 - (int) substr($garagesOfType->max('code') ?? '', 1);

            throw ValidationException::withMessages([
                'quantity' => "Kode sudah dipakai oleh batch lain. Kapasitas tersisa {$remaining} unit. Periksa jumlah dan coba lagi.",
            ]);
        }

        return to_route('garages.create')
            ->with('status', "Berhasil membuat {$quantity} unit garasi.")
            ->with('garage_codes', $codes);
    }
}
