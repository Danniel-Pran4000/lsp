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
    private const TYPES = ['S' => 'Bicycle', 'C' => 'Petrol car', 'E' => 'Electric car'];

    public function index(Request $request, RentalService $rentals): View
    {
        $types = self::TYPES;
        $query = $rentals->availableToday(CarbonImmutable::today());
        $selectedType = TypeFilter::apply($query, 'type', $request->query('type'), $types);
        $garages = $query->orderBy('code')->paginate(20)->appends(['type' => $selectedType]);

        return view('admin.garages', compact('garages', 'types', 'selectedType'));
    }

    public function create(): View
    {
        return view('admin.garage-create', ['type' => old('type', 'S')]);
    }

    public function storeBatch(StoreGarageBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['type_selection'])) {
            return to_route('garages.create')->withInput([
                ...$request->only('quantity', 'daily_rate', 'kwh_rate'),
                'type' => $data['type_selection'],
            ]);
        }
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
                        'quantity' => "Remaining unit codes for garage type {$type}: {$remaining}.",
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
                'quantity' => "Another batch used these codes. {$remaining} unit codes remain. Check the quantity and try again.",
            ]);
        }

        return to_route('admin.rentals')
            ->with('status', self::TYPES[$type].' garage units created. Unit codes: '.implode(', ', $codes).'.');
    }
}
