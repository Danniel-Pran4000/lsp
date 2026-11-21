<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtensionRequest;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExtensionController extends Controller
{
    public function activeRentals(): View
    {
        $today = CarbonImmutable::today()->toDateString();
        $rentals = Rental::with(['garage', 'user'])
            ->where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->orderBy('ends_on')->orderBy('id')
            ->simplePaginate(15);

        return view('admin.active-rentals', compact('rentals'));
    }

    public function preview(ExtensionRequest $request, Rental $rental, RentalService $rentals, RentalCalculator $calculator): View|RedirectResponse
    {
        $rental->load(['garage', 'user']);
        $earliestEnd = CarbonImmutable::parse($rental->ends_on)->addDay()->toDateString();
        $newEnd = $request->validated('ends_on');
        $charge = null;
        $quoteId = null;

        if ($newEnd !== null) {
            try {
                $charge = $rentals->extensionDetails($rental, CarbonImmutable::parse($newEnd), $calculator);
            } catch (ValidationException $exception) {
                return to_route('extensions.preview', $rental)
                    ->withErrors($exception->errors())->withInput(['ends_on' => $newEnd]);
            }

            $quoteId = (string) Str::uuid();
            $request->session()->put('extension_quote', [
                'id' => $quoteId,
                'admin_id' => $request->user()->id,
                'rental_id' => $rental->id,
                'old_end' => $rental->ends_on,
                'new_end' => $newEnd,
                'amount' => $charge['amount'],
            ]);
        }

        return view('admin.rental-extend', compact('rental', 'earliestEnd', 'newEnd', 'charge', 'quoteId'));
    }

    public function store(ExtensionRequest $request, Rental $rental, RentalService $rentals, RentalCalculator $calculator): RedirectResponse
    {
        $quote = $request->session()->get('extension_quote');
        if (($quote['admin_id'] ?? null) !== $request->user()->id
            || ($quote['rental_id'] ?? null) !== $rental->id
            || ($quote['id'] ?? null) !== $request->validated('quote_id')) {
            return to_route('extensions.preview', $rental)
                ->withErrors(['quote' => 'Hitung ulang biaya perpanjangan sebelum menyimpan.']);
        }

        try {
            $rentals->extend($request->user(), $rental, $quote, $request->file('proof'), $calculator);
        } catch (ValidationException $exception) {
            return to_route('extensions.preview', $rental)
                ->withErrors($exception->errors())->withInput(['ends_on' => $quote['new_end']]);
        }

        $request->session()->forget('extension_quote');

        return to_route('admin.rentals')->with('status', "Sewa {$rental->brand} {$rental->vehicle_model} berhasil diperpanjang. Unit {$rental->garage->code}.");
    }
}
