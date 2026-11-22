<?php

namespace App\Services;

use App\Models\Extension;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RentalService
{
    /**
     * Cari unit tanpa sewa aktif pada tanggal acuan agar daftar admin mengikuti jadwal terkini.
     */
    public function vacantUnits(CarbonImmutable $today): Builder
    {
        return Garage::query()->whereNotIn('id', $this->overlappingRentals($today, $today)->select('garage_id'));
    }

    /**
     * Tawarkan unit yang kosong hari ini sekaligus bebas sepanjang periode pilihan.
     * Jadwal sewa menjadi acuan karena status unit yang tersimpan bisa tertinggal saat hari berganti.
     *
     * @return Collection<int, Garage>
     */
    public function findUnits(string $type, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $today = CarbonImmutable::today();
        $garages = Garage::query()->where('type', $type);
        $activeToday = $this->overlappingRentals($today, $today)->select('garage_id');

        (clone $garages)->where('status', 'used')
            ->whereNotIn('id', $activeToday)
            ->update(['status' => 'available']);

        (clone $garages)->where('status', 'available')
            ->whereIn('id', $activeToday)
            ->update(['status' => 'used']);

        $occupied = $this->overlappingRentals($start, $end)->select('garage_id');

        return $this->vacantUnits($today)->where('type', $type)
            ->whereNotIn('id', $occupied)
            ->orderBy('code')->get();
    }

    /**
     * Pratinjau belum mencadangkan unit. Saat konfirmasi, kunci unit untuk mencegah dua
     * pemesanan dengan periode yang bertabrakan, lalu periksa ulang jadwal, tarif, dan persetujuan.
     */
    public function bookRental(User $user, array $quote, UploadedFile $proof, RentalCalculator $calculator): Rental
    {
        return $this->savePayment($proof, 'rental-proofs', function (string $path) use ($user, $quote, $calculator): Rental {
            $details = $quote['details'];
            $garage = Garage::query()->lockForUpdate()->find($details['garage_id']);
            $start = CarbonImmutable::parse($quote['starts_on']);
            $end = CarbonImmutable::parse($quote['ends_on']);
            $today = CarbonImmutable::today();

            abort_unless(User::query()->lockForUpdate()->find($user->id)?->canRent(), 403);

            if ($start->lessThan($today)) {
                throw ValidationException::withMessages(['quote' => 'Tanggal mulai sudah berlalu. Pilih tanggal mulai yang baru.']);
            }

            if (! $garage || $garage->type !== $details['garage_type']
                || $this->overlappingRentals($start, $end)->where('garage_id', $garage->id)->exists()
                || (! $start->isSameDay($today) && $this->overlappingRentals($today, $today)->where('garage_id', $garage->id)->exists())) {
                throw ValidationException::withMessages(['garage_id' => 'Unit ini sudah tidak tersedia. Pilih unit lain.']);
            }

            $charge = $calculator->initialCharge($garage, $details);
            if ($charge['daily_rate'] !== $quote['daily_rate'] || $charge['kwh_rate'] !== $quote['kwh_rate']) {
                throw ValidationException::withMessages(['quote' => 'Tarif berubah. Tinjau total baru dan unggah ulang bukti pembayaran.']);
            }

            $rental = new Rental;
            $rental->forceFill([
                'user_id' => $user->id,
                'garage_id' => $garage->id,
                'vehicle_category' => $details['vehicle_category'],
                'plate' => $details['plate'] ?? null,
                'brand' => $details['brand'],
                'vehicle_model' => $details['vehicle_model'],
                'battery_kwh' => $details['battery_kwh'] ?? null,
                'remaining_percent' => $details['remaining_percent'] ?? null,
                'rental_type' => $details['rental_type'],
                'months' => (int) ($details['months'] ?? 0),
                'days' => (int) $details['days'],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
                'proof_path' => $path,
                ...$charge,
            ])->save();

            $garage->status = $start->isSameDay($today) ? 'used' : 'available';
            $garage->save();

            return $rental;
        });
    }

    /**
     * Periksa benturan hanya pada hari tambahan sebelum menawarkan biaya perpanjangan.
     */
    public function extensionDetails(Rental $rental, CarbonImmutable $newEnd, RentalCalculator $calculator): array
    {
        $charge = $calculator->extensionCharge($rental, $newEnd);
        $additionalStart = CarbonImmutable::parse($rental->ends_on)->addDay();

        if ($this->overlappingRentals($additionalStart, $newEnd)
            ->where('garage_id', $rental->garage_id)
            ->whereKeyNot($rental->id)
            ->exists()) {
            throw ValidationException::withMessages(['ends_on' => 'Periode perpanjangan bertabrakan dengan sewa lain untuk unit ini.']);
        }

        return $charge;
    }

    /**
     * Catat pembayaran dan ubah akhir sewa dalam satu transaksi setelah memeriksa ulang pratinjau admin.
     */
    public function extendRental(User $admin, Rental $rental, array $quote, UploadedFile $proof, RentalCalculator $calculator): Extension
    {
        return $this->savePayment($proof, 'extension-proofs', function (string $path) use ($admin, $rental, $quote, $calculator): Extension {
            // Kunci unit terlebih dahulu agar perpanjangan dan pemesanan unit yang sama diproses bergiliran.
            $garage = Garage::query()->lockForUpdate()->findOrFail($rental->garage_id);
            $currentRental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            abort_unless(User::find($admin->id)?->isAdmin(), 403);

            if ($currentRental->ends_on !== $quote['old_end']) {
                throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai berubah. Hitung ulang biaya perpanjangan.']);
            }

            $today = CarbonImmutable::today();
            $newEnd = CarbonImmutable::parse($quote['new_end']);
            $charge = $this->extensionDetails($currentRental, $newEnd, $calculator);
            if ($charge['amount'] !== $quote['amount']) {
                throw ValidationException::withMessages(['ends_on' => 'Biaya berubah. Hitung ulang biaya perpanjangan.']);
            }

            $extension = Extension::create([
                'rental_id' => $currentRental->id,
                'admin_id' => $admin->id,
                'extended_on' => $today->toDateString(),
                'old_end' => $currentRental->ends_on,
                'new_end' => $newEnd->toDateString(),
                'amount' => $charge['amount'],
                'proof_path' => $path,
            ]);

            $currentRental->ends_on = $extension->new_end;
            $currentRental->save();
            if ($currentRental->coversDate($today) && $garage->status !== 'used') {
                $garage->status = 'used';
                $garage->save();
            }

            return $extension;
        });
    }

    /**
     * Rollback database tidak menghapus berkas di penyimpanan. Jika transaksi gagal,
     * hapus bukti yang baru diunggah agar tidak ada foto tanpa transaksi terkait.
     */
    private function savePayment(UploadedFile $proof, string $directory, Closure $save): Rental|Extension
    {
        $disk = Storage::disk('local');
        $path = $disk->putFile($directory, $proof);
        if ($path === false) {
            throw ValidationException::withMessages(['proof' => 'Bukti pembayaran gagal diunggah. Silakan coba lagi.']);
        }

        try {
            return DB::transaction(fn () => $save($path), 3);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }

    /**
     * Cari sewa yang memakai setidaknya satu hari dalam periode usulan, termasuk kedua tanggal batas.
     */
    private function overlappingRentals(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        // Sewa bertabrakan jika mulai sebelum atau pada akhir usulan dan berakhir
        // setelah atau pada awal usulan. Pertemuan pada satu hari saja tetap benturan.
        return Rental::query()
            ->where('starts_on', '<=', $end->toDateString())
            ->where('ends_on', '>=', $start->toDateString());
    }
}
