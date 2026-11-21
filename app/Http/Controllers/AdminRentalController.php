<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Support\TypeFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRentalController extends Controller
{
    public function index(Request $request): View
    {
        $types = [
            'bicycle' => 'Bicycle',
            'motorcycle' => 'Motorcycle',
            'gas_car' => 'Petrol car',
            'electric_car' => 'Electric car',
        ];
        $query = Rental::with(['garage', 'user']);
        $selectedType = TypeFilter::apply($query, 'vehicle_category', $request->query('type'), $types);
        $rentals = $query->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(20)->appends(['type' => $selectedType]);

        return view('admin.rentals', compact('rentals', 'types', 'selectedType'));
    }
}
