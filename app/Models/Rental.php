<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    public const VEHICLE_LABELS = [
        'bicycle' => 'Sepeda',
        'motorcycle' => 'Sepeda motor',
        'gas_car' => 'Mobil bensin',
        'electric_car' => 'Mobil listrik',
    ];

    public const DURATION_LABELS = ['daily' => 'Harian', 'monthly' => 'Bulanan'];

    public const COMPATIBLE_GARAGES = [
        'bicycle' => ['S'],
        'motorcycle' => ['S'],
        'gas_car' => ['C'],
        'electric_car' => ['C', 'E'],
    ];

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class);
    }

    public function isActiveOn(CarbonImmutable $date): bool
    {
        $day = $date->toDateString();

        return $this->starts_on <= $day && $this->ends_on >= $day;
    }
}
