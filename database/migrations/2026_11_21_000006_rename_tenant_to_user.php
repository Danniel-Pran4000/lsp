<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameUserRole('tenant', 'user');

        Schema::table('rentals', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'user_id');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->renameColumn('user_id', 'tenant_id');
        });

        $this->renameUserRole('user', 'tenant');
    }

    private function renameUserRole(string $oldRole, string $newRole): void
    {
        Schema::table('users', function (Blueprint $table) use ($newRole) {
            $table->string('role')->default($newRole)->change();
        });

        DB::table('users')->where('role', $oldRole)->update(['role' => $newRole]);
    }
};
