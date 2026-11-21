<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class TypeFilter
{
    public static function apply(Builder $query, string $column, mixed $selection, array $options): ?string
    {
        if (! is_string($selection) || ! array_key_exists($selection, $options)) {
            return null;
        }

        $query->where($column, $selection);

        return $selection;
    }
}
