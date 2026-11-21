<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class RentalService
{
    /** @return Collection<int, Garage> */
    public function availableUnits(string $type, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $today = CarbonImmutable::today()->toDateString();
        $garages = Garage::query()->where('type', $type);
        $activeToday = Rental::query()->select('garage_id')
            ->where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today);

        (clone $garages)->where('status', 'used')
            ->whereNotIn('id', $activeToday)
            ->update(['status' => 'available']);

        (clone $garages)->where('status', 'available')
            ->whereIn('id', $activeToday)
            ->update(['status' => 'used']);

        // Tanggal mulai dan selesai ikut dihitung sebagai hari yang ditempati.
        $overlappingRentals = Rental::query()->select('garage_id')
            ->where('starts_on', '<=', $end->toDateString())
            ->where('ends_on', '>=', $start->toDateString());

        return $garages->whereNotIn('id', $overlappingRentals)->orderBy('code')->get();
    }
}
