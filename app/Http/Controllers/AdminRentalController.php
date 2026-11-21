<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rental;
use App\Support\TypeFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRentalController extends Controller
{
    public function index(Request $request): View
    {
        $types = Garage::TYPE_LABELS;
        $vehicleTypes = [
            'bicycle' => 'Bicycle',
            'motorcycle' => 'Motorcycle',
            'gas_car' => 'Petrol car',
            'electric_car' => 'Electric car',
        ];
        $query = Rental::with(['garage', 'user']);
        $selectedType = TypeFilter::apply($query, $request->query('type'), 'garage');
        $rentals = $query->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(20)->appends(['type' => $selectedType]);

        return view('admin.rentals', compact('rentals', 'types', 'vehicleTypes', 'selectedType'));
    }
}
