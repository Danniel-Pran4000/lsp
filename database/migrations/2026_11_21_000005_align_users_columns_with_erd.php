<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lepaskan kolom bawaan autentikasi yang tidak digunakan dalam skema akun GarageRental.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'remember_token', 'created_at', 'updated_at']);
        });
    }

    /**
     * Kembalikan kolom bawaan Laravel untuk memulihkan struktur sebelum penyesuaian ERD.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
};
