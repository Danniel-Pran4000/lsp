<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use App\Support\Rupiah;
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
    /**
     * Tampilkan riwayat milik penyewa yang masuk beserta jumlah sewa aktif dan total setiap transaksi.
     */
    public function listRentals(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isRenter(), 403);

        $ownRentals = Rental::where('user_id', $user->id);
        $today = CarbonImmutable::today()->toDateString();
        $activeCount = (clone $ownRentals)
            ->where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->count();
        $canCreateRental = $user->canRent();
        $approvalStatus = $user->approval_status;
        $rentals = $ownRentals->with('garage')
            ->withSum('extensions', 'amount')
            ->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(15)
            // Format tanggal dan total setelah paginasi agar Blade menerima baris yang siap ditampilkan.
            ->through(function (Rental $rental): array {
                return [
                    'id' => $rental->id,
                    'unit' => $rental->garage->code,
                    'vehicle' => $rental->brand.' '.$rental->vehicle_model,
                    'start' => CarbonImmutable::parse($rental->starts_on)->locale('id')->translatedFormat('d M Y'),
                    'end' => CarbonImmutable::parse($rental->ends_on)->locale('id')->translatedFormat('d M Y'),
                    'total' => Rupiah::formatAmount($rental->totalPaid()),
                ];
            });

        return view('user.rentals', compact('rentals', 'activeCount', 'canCreateRental', 'approvalStatus'));
    }

    /**
     * Sediakan rincian dan riwayat pembayaran yang sama untuk admin dan pemilik transaksi.
     */
    public function showRental(Request $request, Rental $rental): View
    {
        $this->checkAccess($request, $rental);
        $rental->load(['garage', 'user'])->loadSum('extensions', 'amount');
        $extensions = $rental->extensions()->orderBy('id')->paginate(15);
        $originalEnd = $rental->extensions()->orderBy('id')->value('old_end') ?? $rental->ends_on;
        $total = $rental->totalPaid();

        return view('user.detail-rental', [
            ...compact('rental', 'extensions', 'total', 'originalEnd'),
            'vehicle' => Rental::VEHICLE_LABELS[$rental->vehicle_category] ?? $rental->vehicle_category,
            'duration' => Rental::DURATION_LABELS[$rental->rental_type] ?? $rental->rental_type,
            'garageType' => Garage::TYPE_LABELS[$rental->garage->type],
            'backRoute' => $request->user()->isAdmin() ? 'admin.rentals' : 'rentals.index',
        ]);
    }

    /**
     * Kirim gambar dari penyimpanan privat setelah memeriksa akses ke sewa dan kaitan bukti perpanjangannya.
     */
    public function serveProof(Request $request, Rental $rental, ?int $extension = null): StreamedResponse
    {
        $this->checkAccess($request, $rental);
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

    /**
     * Siapkan formulir pemesanan dan data isian untuk memulai atau melanjutkan rencana sewa.
     */
    public function showForm(PlanRentalRequest $request, RentalCalculator $calculator): View
    {
        return view('user.rental-create', $this->preparePlan($request, $calculator));
    }

    /**
     * Saring unit menurut kendaraan dan periode untuk dropdown atau formulir pencarian biasa.
     */
    public function findUnits(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): JsonResponse|View
    {
        $plan = $this->preparePlan($request, $calculator);
        $selectedGarageType = $request->validated('garage_type');

        if (! in_array($selectedGarageType, $plan['garageTypes'], true)) {
            throw ValidationException::withMessages(['garage_type' => 'Tipe garasi tidak sesuai dengan kendaraan.'])
                ->redirectTo(route('rentals.create'));
        }

        $garages = $rentals->findUnits($selectedGarageType, $plan['start'], $plan['end']);
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

    /**
     * Hitung tagihan dari tarif unit terkini dan simpan pratinjau di sesi sebagai acuan konfirmasi.
     */
    public function previewRental(PlanRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): JsonResponse|View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $plan = $this->preparePlan($request, $calculator);
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

        $garage = $rentals->findUnits($details['garage_type'], $start, $end)->find($details['garage_id']);
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

    /**
     * Pastikan bukti pembayaran mengacu pada pratinjau milik akun yang sedang mengonfirmasi.
     */
    public function saveRental(StoreRentalRequest $request, RentalCalculator $calculator, RentalService $rentals): RedirectResponse
    {
        $quote = $request->session()->get('rental_quote');
        if (($quote['user_id'] ?? null) !== $request->user()->id || ($quote['id'] ?? null) !== $request->validated('quote_id')) {
            return to_route('rentals.preview')->withErrors([
                'quote' => 'Tinjau rincian pembayaran dan unggah ulang bukti pembayaran.',
            ]);
        }

        try {
            $rental = $rentals->bookRental($request->user(), $quote, $request->file('proof'), $calculator);
        } catch (ValidationException $exception) {
            return to_route('rentals.preview')->withErrors($exception->errors());
        }

        $request->session()->forget('rental_quote');

        return to_route('rentals.index')->with('status', "Sewa {$rental->brand} {$rental->vehicle_model} berhasil disimpan. Unit {$rental->garage->code}.");
    }

    /**
     * Terapkan hak akses yang sama pada halaman rincian dan URL foto yang bisa dibuka langsung.
     * Admin boleh memeriksa semua sewa. Penyewa tetap boleh membaca riwayat miliknya
     * meskipun status persetujuan akunnya berubah setelah pemesanan.
     */
    private function checkAccess(Request $request, Rental $rental): void
    {
        $user = $request->user();
        $ownsRental = $user->isRenter() && (string) $rental->user_id === (string) $user->id;
        abort_unless($user->isAdmin() || $ownsRental, 404);
    }

    /**
     * Kirim kesalahan validasi ke formulir interaktif atau kembalikan isian saat penyewa memakai formulir biasa.
     */
    private function previewError(PlanRentalRequest $request, array $errors, array $details): RedirectResponse
    {
        if ($request->expectsJson()) {
            throw ValidationException::withMessages($errors);
        }

        return to_route('rentals.create')->withErrors($errors)->withInput($details);
    }

    /**
     * Siapkan tanggal akhir dan tipe garasi yang cocok agar pencarian unit dan pratinjau memakai rencana yang sama.
     */
    private function preparePlan(PlanRentalRequest $request, RentalCalculator $calculator): array
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
