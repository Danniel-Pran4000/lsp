<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['rental_id', 'admin_id', 'extended_on', 'old_end', 'new_end', 'amount', 'proof_path'])]
class Extension extends Model {}
