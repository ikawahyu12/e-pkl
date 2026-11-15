<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip berkas laporan yang pernah dibuat Admin (Riwayat Unduhan).
     * Berkasnya disimpan di disk privat; tabel ini menyimpan metadata.
     */
    public function up(): void
    {
        Schema::create('laporan_arsip', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['presensi', 'jurnal']);
            $table->enum('format', ['xls', 'pdf']);
            $table->unsignedBigInteger('periode_id');
            $table->unsignedBigInteger('jurusan_id')->nullable()->comment('NULL = semua jurusan');
            $table->string('nama_berkas', 190);
            $table->string('path', 255);
            $table->unsignedInteger('ukuran');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['periode_id', 'jurusan_id', 'created_at'], 'idx_laporan_arsip_filter');
            $table->foreign('periode_id', 'fk_laporan_arsip_periode')->references('id')->on('periode_pkl')->cascadeOnDelete();
            $table->foreign('jurusan_id', 'fk_laporan_arsip_jurusan')->references('id')->on('jurusan')->nullOnDelete();
            $table->foreign('dibuat_oleh', 'fk_laporan_arsip_user')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_arsip');
    }
};