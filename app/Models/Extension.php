<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rental_id', 'extended_on', 'old_end', 'new_end', 'amount', 'proof_path'])]
class Extension extends Model
{
    /**
     * Hubungkan pembayaran tambahan ke sewa yang tanggal akhirnya diperpanjang.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
