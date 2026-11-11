<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('penempatan_pkl', function (Blueprint $table) {
        $table->id();
        $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
        $table->string('nama_perusahaan');
        $table->text('alamat_instansi')->nullable();
        $table->string('pembimbing_lapangan')->nullable();
        $table->string('kontak_pembimbing')->nullable();
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('penempatan_pkl');
}
};
