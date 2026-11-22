<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus identitas admin dari riwayat perpanjangan karena aplikasi beroperasi dengan satu admin.
     */
    public function up(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
        });
    }

    /**
     * Pulihkan kolom dan relasi. Biarkan kosong bila identitas admin tidak diketahui.
     */
    public function down(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->constrained('users')->restrictOnDelete();
        });
    }
};
