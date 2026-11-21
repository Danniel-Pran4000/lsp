<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'type', 'daily_rate', 'kwh_rate'])]
class Garage extends Model
{
    public const TYPE_LABELS = ['S' => 'Bicycle', 'C' => 'Petrol car', 'E' => 'Electric car'];
}
