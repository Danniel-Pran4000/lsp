<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan identitas, peran, dan status untuk proses verifikasi pendaftaran.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tenant');
            $table->char('nik', 16)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('approval_status')->nullable()->default('pending');
        });
    }

    /**
     * Lepaskan kolom khusus pendaftaran saat kembali ke skema akun awal.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nik', 'phone', 'approval_status']);
        });
    }
};
