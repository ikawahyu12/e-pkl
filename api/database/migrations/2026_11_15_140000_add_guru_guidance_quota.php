<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Kolom kuota bimbingan hanya didukung pada MySQL.');
        }

        if (! Schema::hasTable('guru') || ! Schema::hasColumn('guru', 'status_bimbingan')) {
            throw new RuntimeException('Skema guru belum tersedia untuk menambah kuota bimbingan.');
        }

        if (Schema::hasColumn('guru', 'kuota_bimbingan')) {
            $column = collect(Schema::getColumns('guru'))
                ->firstWhere('name', 'kuota_bimbingan');
            $type = strtolower((string) ($column['type'] ?? ''));
            $default = trim((string) ($column['default'] ?? ''), "'\"");

            if (
                $type !== 'smallint unsigned'
                || ($column['nullable'] ?? true)
                || $default !== '15'
                || ($column['auto_increment'] ?? false)
            ) {
                throw new RuntimeException(
                    'guru.kuota_bimbingan sudah ada tetapi bukan SMALLINT UNSIGNED NOT NULL DEFAULT 15; '
                    .'rekonsiliasi skema secara manual tanpa mengubah data.'
                );
            }

            return;
        }

        Schema::table('guru', function (Blueprint $table): void {
            $table->unsignedSmallInteger('kuota_bimbingan')
                ->default(15)
                ->after('status_bimbingan');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback otomatis ditolak agar nilai kuota_bimbingan yang sudah ada tidak terhapus.'
        );
    }
};
