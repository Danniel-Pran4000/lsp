<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catat setiap perpanjangan secara terpisah agar bukti, biaya, dan admin pemroses tetap terlacak.
     */
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->date('extended_on')->index();
            $table->date('old_end');
            $table->date('new_end');
            $table->unsignedBigInteger('amount');
            $table->string('proof_path');
            $table->timestamps();
        });
    }

    /**
     * Hapus tabel riwayat perpanjangan saat migrasi dibatalkan.
     */
    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};
