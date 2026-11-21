<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete();
            $table->string('vehicle_category');
            $table->string('plate')->nullable();
            $table->string('brand');
            $table->string('vehicle_model');
            $table->decimal('battery_kwh', 10, 3)->nullable();
            $table->decimal('remaining_percent', 5, 2)->nullable();
            $table->string('rental_type');
            $table->unsignedInteger('months');
            $table->unsignedInteger('days');
            $table->date('starts_on')->index();
            $table->date('ends_on');
            $table->decimal('daily_rate', 14, 2);
            $table->decimal('kwh_rate', 14, 2)->nullable();
            $table->decimal('energy_kwh', 15, 7)->default(0);
            $table->decimal('rental_amount', 22, 2);
            $table->decimal('electricity_amount', 24, 9)->default(0);
            $table->unsignedBigInteger('initial_total');
            $table->string('proof_path');
            $table->timestamps();
            $table->index(['garage_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
