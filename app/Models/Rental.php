<?php

namespace App\Models;

use Brick\Math\BigInteger;
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

    /**
     * Pertahankan dua digit desimal pada data energi dan persentase di semua driver database.
     */
    protected function casts(): array
    {
        return [
            'battery_kwh' => 'decimal:2',
            'remaining_percent' => 'decimal:2',
            'remaining_kwh' => 'decimal:2',
            'energy_kwh' => 'decimal:2',
        ];
    }

    /**
     * Ambil unit yang ditempati untuk menampilkan kode dan tipe garasi pada transaksi.
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Temukan akun pemilik sewa melalui identitas penyewa yang tercatat saat pemesanan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ambil riwayat perpanjangan sebagai dasar rincian pembayaran tambahan.
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class);
    }

    /**
     * Gabungkan pembayaran awal dan seluruh tambahan agar daftar dan rincian menunjukkan total yang sama.
     */
    public function totalPaid(): BigInteger
    {
        // Pakai agregat yang sudah dimuat, termasuk nilai null, agar daftar tidak menjalankan query per baris.
        $extensionsTotal = array_key_exists('extensions_sum_amount', $this->getAttributes())
            ? $this->extensions_sum_amount
            : $this->extensions()->sum('amount');

        return BigInteger::of($this->initial_total)->plus($extensionsTotal ?? 0);
    }

    /**
     * Periksa apakah unit ditempati pada tanggal tertentu, termasuk hari mulai dan hari selesai.
     */
    public function coversDate(CarbonImmutable $date): bool
    {
        $day = $date->toDateString();

        return $this->starts_on <= $day && $this->ends_on >= $day;
    }
}
