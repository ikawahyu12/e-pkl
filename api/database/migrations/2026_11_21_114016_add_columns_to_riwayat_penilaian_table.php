<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('riwayat_penilaian')) {
            throw new RuntimeException(
                'Tabel riwayat_penilaian belum tersedia.'
            );
        }

        Schema::table('riwayat_penilaian', function (Blueprint $table) {
            if (!Schema::hasColumn('riwayat_penilaian', 'penilaian_detail_id')) {
                $table->unsignedBigInteger('penilaian_detail_id')
                    ->after('id');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'nilai_lama')) {
                $table->decimal('nilai_lama', 5, 2)
                    ->nullable()
                    ->after('penilaian_detail_id');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'nilai_baru')) {
                $table->decimal('nilai_baru', 5, 2)
                    ->after('nilai_lama');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'catatan_lama')) {
                $table->text('catatan_lama')
                    ->nullable()
                    ->after('nilai_baru');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'catatan_baru')) {
                $table->text('catatan_baru')
                    ->nullable()
                    ->after('catatan_lama');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'guru_id')) {
                $table->unsignedBigInteger('guru_id')
                    ->after('catatan_baru');
            }

            if (!Schema::hasColumn('riwayat_penilaian', 'diubah_at')) {
                $table->timestamp('diubah_at')
                    ->useCurrent()
                    ->after('guru_id');
            }
        });

        Schema::table('riwayat_penilaian', function (Blueprint $table) {
            $table->foreign('penilaian_detail_id')
                ->references('id')
                ->on('penilaian_detail')
                ->cascadeOnDelete();

            $table->foreign('guru_id')
                ->references('id')
                ->on('guru')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_penilaian', function (Blueprint $table) {
            $table->dropForeign(['penilaian_detail_id']);
            $table->dropForeign(['guru_id']);

            $table->dropColumn([
                'penilaian_detail_id',
                'nilai_lama',
                'nilai_baru',
                'catatan_lama',
                'catatan_baru',
                'guru_id',
                'diubah_at',
            ]);
        });
    }
};
