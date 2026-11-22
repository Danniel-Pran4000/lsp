<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rental;
use App\Support\GarageTypeFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRentalController extends Controller
{
    /**
     * Tampilkan transaksi semua penyewa lintas periode dengan pilihan filter tipe garasi.
     */
    public function listRentals(Request $request): View
    {
        $types = Garage::TYPE_LABELS;
        $query = Rental::with(['garage', 'user']);
        $selectedType = GarageTypeFilter::applyFilter($query, $request->query('type'), 'garage');
        $rentals = $query->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(15)->appends(['type' => $selectedType]);

        return view('admin.rentals', compact('rentals', 'types', 'selectedType'));
    }
}
