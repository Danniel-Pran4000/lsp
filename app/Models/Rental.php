<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rental extends Model
{
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActiveOn(CarbonImmutable $date): bool
    {
        $day = $date->toDateString();

        return $this->starts_on <= $day && $this->ends_on >= $day;
    }
}
