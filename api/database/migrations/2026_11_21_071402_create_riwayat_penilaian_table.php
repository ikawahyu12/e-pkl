<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel mungkin sudah dibuat sebelumnya.
        if (!Schema::hasTable('riwayat_penilaian')) {
            Schema::create('riwayat_penilaian', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus tabel karena tabel ini sudah ada
        // sebelum migration ini dijalankan.
    }
};
