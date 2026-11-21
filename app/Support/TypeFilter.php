<?php

namespace App\Support;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Builder;

class TypeFilter
{
    public static function apply(Builder $query, mixed $selection, ?string $garageRelation = null): ?string
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
