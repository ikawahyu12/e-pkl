<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'agenda_kunjungan';

    private const LEGACY_TABLE = 'agenda_kunjungan_legacy';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'penempatan_id')) {
            return;
        }

        if (DB::table(self::TABLE)->exists()) {
            throw new RuntimeException(
                'Skema agenda lama berisi data. Migrasi otomatis dihentikan agar waktu dan tujuan agenda '
                .'tidak hilang; ekspor dan rekonsiliasi data lama sebelum menjalankan migration kembali.'
            );
        }

        if (Schema::hasTable('agenda_kunjungan_siswa')) {
            throw new RuntimeException(
                'Ditemukan tabel agenda_kunjungan_siswa bersama skema agenda lama; '
                .'rekonsiliasi tabel relasi secara manual sebelum melanjutkan.'
            );
        }

        if (Schema::hasTable(self::LEGACY_TABLE)) {
            throw new RuntimeException(
                'Tabel arsip agenda_kunjungan_legacy sudah ada. Periksa arsip tersebut sebelum melanjutkan.'
            );
        }

        Schema::rename(self::TABLE, self::LEGACY_TABLE);

        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('guru_id')->index('idx_agenda_kunjungan_guru');
            $table->unsignedBigInteger('instansi_id')->index('idx_agenda_kunjungan_instansi');
            $table->date('tanggal')->index('idx_agenda_kunjungan_tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status', 20)->default('terjadwal');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('agenda_kunjungan_siswa', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('agenda_kunjungan_id');
            $table->unsignedBigInteger('siswa_id');
            $table->primary(['agenda_kunjungan_id', 'siswa_id']);
            $table->foreign('agenda_kunjungan_id', 'fk_agenda_siswa_agenda')
                ->references('id')
                ->on(self::TABLE)
                ->restrictOnDelete();
            $table->foreign('siswa_id', 'fk_agenda_siswa_siswa')
                ->references('id')
                ->on('siswa')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback otomatis ditolak agar arsip serta data agenda tetap terjaga.'
        );
    }
};
