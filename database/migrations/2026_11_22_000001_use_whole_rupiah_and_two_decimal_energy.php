<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Terapkan rupiah bulat dan presisi energi dua desimal pada data yang sudah tersimpan.
     */
    public function up(): void
    {
        // MariaDB membulatkan saat konversi tipe. SQLite tidak menerapkan skala desimal.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->roundColumns('garages', ['daily_rate' => 0, 'kwh_rate' => 0]);
            $this->roundColumns('rentals', [
                'daily_rate' => 0,
                'kwh_rate' => 0,
                'rental_amount' => 0,
                'electricity_amount' => 0,
                'battery_kwh' => 2,
                'energy_kwh' => 2,
                'remaining_kwh' => 2,
            ]);
        }

        Schema::table('garages', function (Blueprint $table) {
            $table->unsignedBigInteger('daily_rate')->change();
            $table->unsignedBigInteger('kwh_rate')->nullable()->change();
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->unsignedBigInteger('daily_rate')->change();
            $table->unsignedBigInteger('kwh_rate')->nullable()->change();
            $table->unsignedBigInteger('rental_amount')->change();
            $table->unsignedBigInteger('electricity_amount')->default(0)->change();
            $table->decimal('battery_kwh', 10, 2)->nullable()->change();
            $table->decimal('energy_kwh', 10, 2)->default(0)->change();
            $table->decimal('remaining_kwh', 10, 2)->nullable()->change();
        });
    }

    /**
     * Pulihkan tipe kolom sebelumnya. Pecahan yang sudah dibulatkan tidak dapat dipulihkan dari skema.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->decimal('daily_rate', 14, 2)->change();
            $table->decimal('kwh_rate', 14, 2)->nullable()->change();
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('daily_rate', 14, 2)->change();
            $table->decimal('kwh_rate', 14, 2)->nullable()->change();
            $table->decimal('rental_amount', 22, 2)->change();
            $table->decimal('electricity_amount', 24, 9)->default(0)->change();
            $table->decimal('battery_kwh', 10, 3)->nullable()->change();
            $table->decimal('energy_kwh', 15, 7)->default(0)->change();
            $table->decimal('remaining_kwh', 10, 3)->nullable()->change();
        });
    }

    /**
     * Bulatkan nilai lama saat SQLite tidak melakukan konversi skala secara otomatis.
     */
    private function roundColumns(string $table, array $scales): void
    {
        foreach ($scales as $column => $scale) {
            $rounded = "ROUND({$column}, {$scale})";

            DB::table($table)->whereRaw("{$column} <> {$rounded}")
                ->update([$column => DB::raw($rounded)]);
        }
    }
};
