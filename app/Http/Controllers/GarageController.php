<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Services\RentalService;
use App\Support\TypeFilter;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function index(Request $request, RentalService $rentals): View
    {
        $types = Garage::TYPE_LABELS;
        $query = $rentals->availableToday(CarbonImmutable::today());
        $selectedType = TypeFilter::apply($query, $request->query('type'));
        $garages = $query->orderBy('code')->paginate(15)->appends(['type' => $selectedType]);

        return view('admin.garages', compact('garages', 'types', 'selectedType'));
    }

    public function create(): View
    {
        return view('admin.garage-create', [
            'type' => old('type', 'S'),
            'types' => Garage::TYPE_LABELS,
            'maxUnits' => Garage::MAX_UNITS_PER_TYPE,
        ]);
    }

    public function storeBatch(StoreGarageBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'];
        $quantity = (int) $data['quantity'];
        // Kode berawalan sama dengan tiga digit dapat diurutkan sebagai teks.
        $garagesOfType = Garage::whereBetween('code', [$type.'001', $type.Garage::MAX_UNITS_PER_TYPE]);

        try {
            $codes = DB::transaction(function () use ($garagesOfType, $type, $quantity, $data): array {
                $lastNumber = (int) substr($garagesOfType->max('code') ?? '', 1);
                $remaining = Garage::MAX_UNITS_PER_TYPE - $lastNumber;

                if ($quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'quantity' => "Sisa kapasitas tipe garasi {$type} adalah {$remaining} unit.",
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
            $remaining = Garage::MAX_UNITS_PER_TYPE - (int) substr($garagesOfType->max('code') ?? '', 1);

            throw ValidationException::withMessages([
                'quantity' => "Kode sudah dipakai oleh pembuatan unit lain. Tersisa {$remaining} unit. Periksa jumlah lalu coba lagi.",
            ]);
        }

        return to_route('garages.index')
            ->with('status', count($codes).' unit berhasil ditambahkan. Kode unit '.implode(', ', $codes).'.');
    }
}
