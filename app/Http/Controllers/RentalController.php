<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRentalRequest;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function create(PlanRentalRequest $request, RentalCalculator $calculator): View
    {
        return view('rentals.create', $this->plan($request, $calculator));
    }

    public function available(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): View
    {
        $plan = $this->plan($request, $calculator);
        $selectedGarageType = $request->validated('garage_type');

        if (! in_array($selectedGarageType, $plan['garageTypes'], true)) {
            throw ValidationException::withMessages(['garage_type' => 'Tipe garasi tidak cocok dengan kendaraan.'])
                ->redirectTo(route('rentals.create'));
        }

        return view('rentals.create', [
            ...$plan,
            'selectedGarageType' => $selectedGarageType,
            'garages' => $rentals->availableUnits($selectedGarageType, $plan['start'], $plan['end']),
        ]);
    }

    private function plan(PlanRentalRequest $request, RentalCalculator $calculator): array
    {
        $start = CarbonImmutable::today();
        $details = $request->validated();
        $end = null;
        $garageTypes = [];

        if ($details !== []) {
            $end = $calculator->endDate($start, (int) ($details['months'] ?? 0), (int) $details['days']);
            if ($end->year > 9999) {
                throw ValidationException::withMessages(['days' => 'Tanggal selesai melampaui rentang tanggal yang didukung.'])
                    ->redirectTo(route('rentals.create'));
            }

            $garageTypes = match ($details['vehicle_category']) {
                'bicycle', 'motorcycle' => ['S'],
                'gas_car' => ['C'],
                'electric_car' => ['C', 'E'],
            };
        }

        return compact('start', 'end', 'details', 'garageTypes');
    }
}
