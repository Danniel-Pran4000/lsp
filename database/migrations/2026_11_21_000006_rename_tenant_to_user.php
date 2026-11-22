<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selaraskan nama peran dan kolom pemilik sewa dengan istilah user pada aplikasi.
     */
    public function up(): void
    {
        $this->renameRole('tenant', 'user');

        Schema::table('rentals', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'user_id');
        });
    }

    /**
     * Pulihkan nama peran dan kolom pemilik untuk versi skema sebelumnya.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->renameColumn('user_id', 'tenant_id');
        });

        $this->renameRole('user', 'tenant');
    }

    /**
     * Ubah nilai bawaan dan akun yang sudah ada agar keduanya memakai nama peran yang sama.
     */
    private function renameRole(string $oldRole, string $newRole): void
    {
        Schema::table('users', function (Blueprint $table) use ($newRole) {
            $table->string('role')->default($newRole)->change();
        });

        DB::table('users')->where('role', $oldRole)->update(['role' => $newRole]);
    }
};
