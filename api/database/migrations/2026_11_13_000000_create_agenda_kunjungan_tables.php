<?php

// Simpan di: database/migrations/2026_11_13_000000_create_agenda_kunjungan_tables.php
// Lalu jalankan: php artisan migrate
//
// Kolom guru_id / instansi_id / siswa_id sengaja tanpa foreign key ke tabel lain,
// karena nama tabel dan tipe id-nya belum kulihat. Kalau mau, tambahkan sendiri.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guru_id')->index();
            $table->unsignedBigInteger('instansi_id')->index();
            $table->date('tanggal')->index();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status', 20)->default('terjadwal'); // terjadwal | selesai
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('agenda_kunjungan_siswa', function (Blueprint $table) {
            $table->unsignedBigInteger('agenda_kunjungan_id');
            $table->unsignedBigInteger('siswa_id');
            $table->primary(['agenda_kunjungan_id', 'siswa_id']);
            $table->foreign('agenda_kunjungan_id')
                ->references('id')->on('agenda_kunjungan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_kunjungan_siswa');
        Schema::dropIfExists('agenda_kunjungan');
    }
};