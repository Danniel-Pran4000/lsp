<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Extension;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(ReportRequest $request): View
    {
        $filters = $request->validated();
        $today = CarbonImmutable::today();
        $mode = $filters['mode'] ?? 'monthly';
        $year = (int) ($filters['year'] ?? $today->year);
        $month = (int) ($filters['month'] ?? $today->month);
        $garageType = $filters['garage_type'] ?? null;
        $userId = isset($filters['user_id']) ? (int) $filters['user_id'] : null;
        $monthNames = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $garageTypes = Garage::TYPE_LABELS;
        $renters = collect();

        $rentals = Rental::query();
        $extensions = Extension::query()->join('rentals', 'rentals.id', '=', 'extensions.rental_id');

        switch ($mode) {
            case 'monthly':
            case 'daily':
                $start = CarbonImmutable::create($year, $mode === 'daily' ? $month : 1, 1);
                $end = $mode === 'daily' ? $start->endOfMonth() : $start->endOfYear();
                $dates = [$start->toDateString(), $end->toDateString()];
                $rentals->whereBetween('rentals.starts_on', $dates);
                $extensions->whereBetween('extensions.extended_on', $dates);

                $keyOffset = $mode === 'monthly' ? 6 : 9;
                $rentalGroup = "substr(rentals.starts_on, {$keyOffset}, 2)";
                $extensionGroup = "substr(extensions.extended_on, {$keyOffset}, 2)";
                $labels = $mode === 'monthly' ? $monthNames : range(1, $start->daysInMonth);
                $groups = [];
                foreach ($labels as $key => $label) {
                    $group = sprintf('%02d', $mode === 'monthly' ? $key : $label);
                    $groups[$group] = $mode === 'monthly' ? $label : "{$group} {$monthNames[$month]}";
                }
                break;

            case 'type':
                $rentals->join('garages', 'garages.id', '=', 'rentals.garage_id');
                $extensions->join('garages', 'garages.id', '=', 'rentals.garage_id');
                $groups = $garageTypes;
                if ($garageType !== null) {
                    $rentals->where('garages.type', $garageType);
                    $extensions->where('garages.type', $garageType);
                    $groups = [$garageType => $garageTypes[$garageType]];
                }
                $rentalGroup = $extensionGroup = 'garages.type';
                break;

            default: // Per penyewa.
                $renters = User::where('role', 'user')->orderBy('name')->orderBy('id')->get(['id', 'name']);
                $groups = $renters->pluck('name', 'id')->all();
                if ($userId !== null) {
                    $rentals->where('rentals.user_id', $userId);
                    $extensions->where('rentals.user_id', $userId);
                    $groups = array_intersect_key($groups, [$userId => true]);
                }
                $rentalGroup = $extensionGroup = 'rentals.user_id';
                break;
        }

        $rentalTotals = $this->totalsByGroup($rentals, $rentalGroup, 'rentals.initial_total');
        $extensionTotals = $this->totalsByGroup($extensions, $extensionGroup, 'extensions.amount');
        $lastSummaryPage = max(1, (int) ceil(count($groups) / 15));
        $summaryPage = min(LengthAwarePaginator::resolveCurrentPage('summary_page'), $lastSummaryPage);
        $groups = (new LengthAwarePaginator(
            array_slice($groups, ($summaryPage - 1) * 15, 15, true),
            count($groups),
            15,
            $summaryPage,
            ['path' => $request->url(), 'pageName' => 'summary_page'],
        ))->withQueryString();

        $rentalDetails = $rentals->with(['garage', 'user'])
            ->orderByDesc('rentals.starts_on')->orderByDesc('rentals.id')
            ->simplePaginate(15, ['rentals.*'], 'rental_page')->withQueryString();
        $extensionDetails = $extensions->with(['rental.garage', 'rental.user'])
            ->orderByDesc('extensions.extended_on')->orderByDesc('extensions.id')
            ->simplePaginate(15, ['extensions.*'], 'extension_page')->withQueryString();

        return view('admin.reports', compact(
            'mode', 'year', 'month', 'garageType', 'userId', 'monthNames', 'garageTypes', 'renters',
            'groups', 'rentalTotals', 'extensionTotals', 'rentalDetails', 'extensionDetails',
        ));
    }

    private function totalsByGroup(Builder $query, string $group, string $amount): Collection
    {
        // Ekspresi pengelompokan dan kolom nominal ditentukan di controller.
        return (clone $query)->toBase()
            ->selectRaw("{$group} as report_group, count(*) as transaction_count, sum({$amount}) as total")
            ->groupByRaw($group)
            ->get()->keyBy('report_group');
    }
}
