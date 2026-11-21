<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use App\Support\Rupiah;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'user', 403);

        $ownRentals = Rental::where('user_id', $user->id);
        $today = CarbonImmutable::today()->toDateString();
        $activeCount = (clone $ownRentals)
            ->where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->count();
        $canCreateRental = $user->canRent();
        $rentals = $ownRentals->with('garage')
            ->withSum('extensions', 'amount')
            ->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(15)
            ->through(function (Rental $rental): array {
                $total = BigInteger::of($rental->initial_total)->plus($rental->extensions_sum_amount ?? 0);

                return [
                    'id' => $rental->id,
                    'unit' => $rental->garage->code,
                    'vehicle' => $rental->brand.' '.$rental->vehicle_model,
                    'start' => CarbonImmutable::parse($rental->starts_on)->locale('id')->translatedFormat('d M Y'),
                    'end' => CarbonImmutable::parse($rental->ends_on)->locale('id')->translatedFormat('d M Y'),
                    'total' => Rupiah::format($total),
                ];
            });

        return view('user.rentals', compact('rentals', 'activeCount', 'canCreateRental'));
    }

    public function show(Request $request, Rental $rental): View
    {
        $this->ensureOwner($request, $rental);
        $rental->load(['garage', 'user'])->loadSum('extensions', 'amount');
        $extensions = $rental->extensions()->orderBy('id')->paginate(15);
        $originalEnd = $rental->extensions()->orderBy('id')->value('old_end') ?? $rental->ends_on;
        $total = BigInteger::of($rental->initial_total)->plus($rental->extensions_sum_amount ?? 0);

        return view('user.detail-rental', [
            ...compact('rental', 'extensions', 'total', 'originalEnd'),
            'vehicle' => Rental::VEHICLE_LABELS[$rental->vehicle_category] ?? $rental->vehicle_category,
            'duration' => Rental::DURATION_LABELS[$rental->rental_type] ?? $rental->rental_type,
            'garageType' => Garage::TYPE_LABELS[$rental->garage->type],
        ]);
    }

    public function proof(Request $request, Rental $rental, ?int $extension = null): StreamedResponse
    {
        $this->ensureOwner($request, $rental);
        $path = $extension === null
            ? $rental->proof_path
            : $rental->extensions()->findOrFail($extension)->proof_path;

        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/webp'], true), 404);

        return $disk->response($path, headers: [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function create(PlanRentalRequest $request, RentalCalculator $calculator): View
    {
        return view('user.rental-create', $this->plan($request, $calculator));
    }

    public function available(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): JsonResponse|View
    {
        $plan = $this->plan($request, $calculator);
        $selectedGarageType = $request->validated('garage_type');

        if (! in_array($selectedGarageType, $plan['garageTypes'], true)) {
            throw ValidationException::withMessages(['garage_type' => 'Tipe garasi tidak sesuai dengan kendaraan.'])
                ->redirectTo(route('rentals.create'));
        }

        $garages = $rentals->availableUnits($selectedGarageType, $plan['start'], $plan['end']);
        if ($request->expectsJson()) {
            return response()->json([
                'end' => $plan['end']->format('d/m/Y'),
                'units' => $garages->map->only(['id', 'code'])->values(),
            ]);
        }

        return view('user.rental-create', [
            ...$plan,
            'selectedGarageType' => $selectedGarageType,
            'garages' => $garages,
        ]);
    }

    public function preview(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): JsonResponse|View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $plan = $this->plan($request, $calculator);
            if (! in_array($plan['details']['garage_type'], $plan['garageTypes'], true)) {
                return $this->previewError($request, ['garage_type' => 'Tipe garasi tidak sesuai dengan kendaraan.'], $plan['details']);
            }

            $request->session()->put('rental_quote', [
                'user_id' => $request->user()->id,
                'details' => $plan['details'],
                'starts_on' => $plan['start']->toDateString(),
                'ends_on' => $plan['end']->toDateString(),
            ]);

            if (! $request->expectsJson()) {
                return to_route('rentals.preview');
            }
        }

        $quote = $request->session()->get('rental_quote');
        if (! $quote || $quote['user_id'] !== $request->user()->id) {
            return to_route('rentals.create');
        }

        $start = CarbonImmutable::parse($quote['starts_on']);
        $end = CarbonImmutable::parse($quote['ends_on']);
        $details = $quote['details'];

        if ($start->lessThan(CarbonImmutable::today())) {
            return $this->previewError($request, ['quote' => 'Tanggal mulai sudah berlalu. Pilih tanggal mulai yang baru.'], $details);
        }

        $garage = $rentals->availableUnits($details['garage_type'], $start, $end)->find($details['garage_id']);
        if (! $garage) {
            return $this->previewError($request, ['garage_id' => 'Unit ini sudah tidak tersedia. Pilih unit lain.'], $details);
        }

        try {
            $charge = $calculator->initialCharge($garage, $details);
        } catch (ValidationException $exception) {
            return $this->previewError($request, $exception->errors(), $details);
        }

        $quoteId = (string) Str::uuid();
        $request->session()->put('rental_quote', array_replace($quote, [
            'id' => $quoteId,
            'daily_rate' => $charge['daily_rate'],
            'kwh_rate' => $charge['kwh_rate'],
        ]));

        $summary = compact('garage', 'start', 'end', 'charge');
        if ($request->expectsJson()) {
            return response()->json([
                'quoteId' => $quoteId,
                'summary' => view('user.rental-summary', $summary)->render(),
            ]);
        }

        return view('user.rental-preview', [...$summary, 'quoteId' => $quoteId]);
    }

    public function store(StoreRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): RedirectResponse
    {
        $quote = $request->session()->get('rental_quote');
        if (($quote['user_id'] ?? null) !== $request->user()->id || ($quote['id'] ?? null) !== $request->validated('quote_id')) {
            return to_route('rentals.preview')->withErrors([
                'quote' => 'Tinjau rincian pembayaran dan unggah ulang bukti pembayaran.',
            ]);
        }

        try {
            $rental = $rentals->book($request->user(), $quote, $request->file('proof'), $calculator);
        } catch (ValidationException $exception) {
            return to_route('rentals.preview')->withErrors($exception->errors());
        }

        $request->session()->forget('rental_quote');

        return to_route('rentals.index')->with('status', "Sewa {$rental->brand} {$rental->vehicle_model} berhasil disimpan. Unit {$rental->garage->code}.");
    }

    private function ensureOwner(Request $request, Rental $rental): void
    {
        $user = $request->user();
        abort_unless($user->role === 'user' && (string) $rental->user_id === (string) $user->id, 404);
    }

    private function previewError(PlanRentalRequest $request, array $errors, array $details): RedirectResponse
    {
        if ($request->expectsJson()) {
            throw ValidationException::withMessages($errors);
        }

        return to_route('rentals.create')->withErrors($errors)->withInput($details);
    }

    private function plan(PlanRentalRequest $request, RentalCalculator $calculator): array
    {
        $details = $request->validated();
        $start = CarbonImmutable::parse($details['starts_on'] ?? 'today');
        $end = null;
        $garageTypes = [];

        if ($details !== []) {
            $details['starts_on'] = $start->toDateString();
            $details['days'] = (int) ($details['days'] ?? 0);
            $end = $calculator->endDate($start, (int) ($details['months'] ?? 0), (int) $details['days']);
            if ($end->year > 9999) {
                throw ValidationException::withMessages(['days' => 'Tanggal selesai melampaui rentang tanggal yang didukung.'])
                    ->redirectTo(route('rentals.create'));
            }

            $garageTypes = Rental::COMPATIBLE_GARAGES[$details['vehicle_category']];
        }

        $details = $details ?: $request->session()->get('rental_quote.details', []);

        return [
            ...compact('start', 'end', 'details', 'garageTypes'),
            'vehicles' => Rental::VEHICLE_LABELS,
            'durations' => Rental::DURATION_LABELS,
            'garageLabels' => Garage::TYPE_LABELS,
            'compatibleGarages' => Rental::COMPATIBLE_GARAGES,
        ];
    }
}
