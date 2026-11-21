<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\View\View;

class AdminRentalController extends Controller
{
    public function index(): View
    {
        $rentals = Rental::with(['garage', 'user'])
            ->orderByDesc('starts_on')->orderByDesc('id')->paginate(20);

        return view('admin.rentals', compact('rentals'));
    }
}
