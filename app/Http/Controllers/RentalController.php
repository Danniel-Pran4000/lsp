<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'user', 403);

        $ownRentals = Rental::where('user_id', $user->id);
        $garageCount = (clone $ownRentals)->distinct()->count('garage_id');
        $rentals = $ownRentals->with('garage')
            ->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(20);

        return view('rentals.index', compact('rentals', 'garageCount'));
    }

    public function create(PlanRentalRequest $request, RentalCalculator $calculator): View
    {
        return view('rentals.create', $this->plan($request, $calculator));
    }

    public function available(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): View
    {
        $plan = $this->plan($request, $calculator);
        $selectedGarageType = $request->validated('garage_type');

        if (! in_array($selectedGarageType, $plan['garageTypes'], true)) {
            throw ValidationException::withMessages(['garage_type' => 'The garage type is not compatible with the vehicle.'])
                ->redirectTo(route('rentals.create'));
        }

        return view('rentals.create', [
            ...$plan,
            'selectedGarageType' => $selectedGarageType,
            'garages' => $rentals->availableUnits($selectedGarageType, $plan['start'], $plan['end']),
        ]);
    }

    public function preview(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $plan = $this->plan($request, $calculator);
            if (! in_array($plan['details']['garage_type'], $plan['garageTypes'], true)) {
                return to_route('rentals.create')
                    ->withErrors(['garage_type' => 'The garage type is not compatible with the vehicle.'])
                    ->withInput($plan['details']);
            }

            $request->session()->put('rental_quote', [
                'user_id' => $request->user()->id,
                'details' => $plan['details'],
                'starts_on' => $plan['start']->toDateString(),
                'ends_on' => $plan['end']->toDateString(),
            ]);

            return to_route('rentals.preview');
        }

        $quote = $request->session()->get('rental_quote');
        if (! $quote || $quote['user_id'] !== $request->user()->id) {
            return to_route('rentals.create');
        }

        $start = CarbonImmutable::parse($quote['starts_on']);
        $end = CarbonImmutable::parse($quote['ends_on']);
        $details = $quote['details'];

        if ($start->lessThan(CarbonImmutable::today())) {
            return to_route('rentals.create')
                ->withErrors(['quote' => 'The start date has passed. Choose a new start date.'])
                ->withInput($details);
        }

        $garage = $rentals->availableUnits($details['garage_type'], $start, $end)->find($details['garage_id']);
        if (! $garage) {
            return to_route('rentals.create')
                ->withErrors(['garage_id' => 'This unit is no longer available. Choose another unit.'])
                ->withInput($details);
        }

        try {
            $charge = $calculator->initialCharge($garage, $details);
        } catch (ValidationException $exception) {
            return to_route('rentals.create')->withErrors($exception->errors())->withInput($details);
        }

        $quoteId = (string) Str::uuid();
        $request->session()->put('rental_quote', array_replace($quote, [
            'id' => $quoteId,
            'daily_rate' => $charge['daily_rate'],
            'kwh_rate' => $charge['kwh_rate'],
        ]));

        return view('rentals.preview', compact('garage', 'start', 'end', 'charge', 'quoteId'));
    }

    public function store(StoreRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): RedirectResponse
    {
        $quote = $request->session()->get('rental_quote');
        if (($quote['user_id'] ?? null) !== $request->user()->id || ($quote['id'] ?? null) !== $request->validated('quote_id')) {
            return to_route('rentals.preview')->withErrors([
                'quote' => 'Review the payment details and upload payment proof again.',
            ]);
        }

        try {
            $rental = $rentals->book($request->user(), $quote, $request->file('proof'), $calculator);
        } catch (ValidationException $exception) {
            return to_route('rentals.preview')->withErrors($exception->errors());
        }

        $request->session()->forget('rental_quote');

        return to_route('rentals.index')->with('status', "Rental #{$rental->id} saved. Unit {$rental->garage->code}, {$rental->starts_on} to {$rental->ends_on}.");
    }

    private function plan(PlanRentalRequest $request, RentalCalculator $calculator): array
    {
        $details = $request->validated();
        $start = CarbonImmutable::parse($details['starts_on'] ?? 'today');
        $end = null;
        $garageTypes = [];

        if ($details !== []) {
            $details['starts_on'] = $start->toDateString();
            $end = $calculator->endDate($start, (int) ($details['months'] ?? 0), (int) $details['days']);
            if ($end->year > 9999) {
                throw ValidationException::withMessages(['days' => 'The end date exceeds the supported date range.'])
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
