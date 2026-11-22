<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Laravel memanggil method ini saat mendaftarkan layanan aplikasi. Saat ini semua layanan memakai resolusi bawaan.
     */
    public function register(): void
    {
        //
    }

    /**
     * Laravel memanggil method ini setelah layanan terdaftar. Saat ini aplikasi tidak memerlukan inisialisasi tambahan.
     */
    public function boot(): void
    {
        //
    }
}
