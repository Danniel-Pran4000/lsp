<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sediakan kolom sisa energi yang boleh kosong untuk skema yang mendukung nilai kWh.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('remaining_kwh', 10, 3)->nullable();
        });
    }

    /**
     * Lepaskan kolom sisa kWh saat kembali ke versi skema sebelumnya.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('remaining_kwh');
        });
    }
};
