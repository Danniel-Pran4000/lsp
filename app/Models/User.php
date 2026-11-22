<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'nik', 'phone'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * Tentukan apakah akun berwenang menjalankan tindakan khusus admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Kenali peran penyewa secara terpisah dari status persetujuan pendaftarannya.
     */
    public function isRenter(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Izinkan pemesanan baru hanya untuk penyewa yang sudah disetujui admin.
     */
    public function canRent(): bool
    {
        return $this->isRenter() && $this->approval_status === 'approved';
    }

    /**
     * Hash kata sandi saat disimpan agar basis data hanya menyimpan hasil hash.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
