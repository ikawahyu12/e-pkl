<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sesi_presensi_penempatan')) {
            return;
        }

        Schema::create('sesi_presensi_penempatan', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('sesi_id');
            $table->unsignedBigInteger('penempatan_id');
            $table->primary(['sesi_id', 'penempatan_id']);
            $table->index('penempatan_id', 'idx_sesi_presensi_penempatan_placement');
            $table->foreign('sesi_id', 'fk_sesi_presensi_placement_session')
                ->references('id')
                ->on('sesi_presensi')
                ->restrictOnDelete();
            $table->foreign('penempatan_id', 'fk_sesi_presensi_placement_placement')
                ->references('id')
                ->on('penempatan_pkl')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback otomatis ditolak agar penetapan peserta sesi presensi tetap terjaga.'
        );
    }
};
