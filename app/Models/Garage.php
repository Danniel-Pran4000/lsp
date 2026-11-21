<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'type', 'daily_rate', 'kwh_rate'])]
class Garage extends Model
{
    public const MAX_UNITS_PER_TYPE = 999;

    public const TYPE_LABELS = ['S' => 'Sepeda', 'C' => 'Mobil bensin', 'E' => 'Mobil listrik'];
}
