<?php

namespace App\Support;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Builder;

class GarageTypeFilter
{
    /**
     * Terapkan pilihan tipe pada daftar garasi atau relasi garasi milik sewa agar kedua tab memakai kriteria yang sama.
     */
    public static function applyFilter(Builder $query, mixed $selection, ?string $garageRelation = null): ?string
    {
        if (! is_string($selection) || ! array_key_exists($selection, Garage::TYPE_LABELS)) {
            return null;
        }

        if ($garageRelation === null) {
            $query->where('type', $selection);
        } else {
            $query->whereRelation($garageRelation, 'type', $selection);
        }

        return $selection;
    }
}
