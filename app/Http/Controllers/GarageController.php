<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Services\RentalService;
use App\Support\GarageTypeFilter;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GarageController extends Controller
{
    /**
     * Tampilkan unit yang kosong hari ini sesuai filter tipe yang dipilih admin.
     */
    public function listGarages(Request $request, RentalService $rentals): View
    {
        $types = Garage::TYPE_LABELS;
        $query = $rentals->vacantUnits(CarbonImmutable::today());
        $selectedType = GarageTypeFilter::applyFilter($query, $request->query('type'));
        $garages = $query->orderBy('code')->paginate(15)->appends(['type' => $selectedType]);

        return view('admin.garages', compact('garages', 'types', 'selectedType'));
    }

    /**
     * Siapkan pilihan tipe dan batas kapasitas untuk formulir penambahan unit.
     */
    public function showForm(): View
    {
        return view('admin.garage-create', [
            'type' => old('type', 'S'),
            'types' => Garage::TYPE_LABELS,
            'maxUnits' => Garage::MAX_UNITS_PER_TYPE,
        ]);
    }

    /**
     * Alokasikan kode berurutan per tipe dalam satu transaksi agar penambahan unit berhasil atau batal sebagai satu kelompok.
     */
    public function createUnits(StoreGarageBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['type'];
        $quantity = (int) $data['quantity'];
        // Lebar tiga digit membuat urutan teks sama dengan urutan nomor untuk tipe yang sama.
        $garagesOfType = Garage::whereBetween('code', [$type.'001', $type.Garage::MAX_UNITS_PER_TYPE]);

        try {
            // Periksa kapasitas dan masukkan seluruh batch dalam satu transaksi.
            // Constraint unik melindungi kode jika permintaan lain mengambil nomor yang sama.
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
