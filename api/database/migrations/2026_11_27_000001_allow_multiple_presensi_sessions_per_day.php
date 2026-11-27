<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'sesi_presensi';

    private const COLUMNS = ['pengaturan_id', 'tanggal', 'nama_shift'];

    private const INDEX_NAME = 'sesi_presensi_pengaturan_id_tanggal_nama_shift_unique';

    private const SETTINGS_INDEX_NAME = 'idx_sesi_presensi_pengaturan_id';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $index = $this->findEquivalentUniqueIndex();
        if (! $index) {
            return;
        }

        if (! $this->hasNonUniqueSettingsIndex()) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index(['pengaturan_id'], self::SETTINGS_INDEX_NAME);
            });
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($index): void {
            $table->dropUnique($index['name']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || $this->findEquivalentUniqueIndex()) {
            return;
        }

        $hasDuplicates = DB::table(self::TABLE)
            ->select(self::COLUMNS)
            ->groupBy(self::COLUMNS)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($hasDuplicates) {
            throw new RuntimeException(
                'Rollback ditolak: terdapat sesi presensi ganda pada tanggal dan instansi yang sama.'
            );
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(self::COLUMNS, self::INDEX_NAME);
        });
    }

    private function findEquivalentUniqueIndex(): ?array
    {
        $columns = self::COLUMNS;
        sort($columns);

        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (! ($index['unique'] ?? false)) {
                continue;
            }

            $indexColumns = $index['columns'] ?? [];
            sort($indexColumns);
            if ($indexColumns === $columns) {
                return $index;
            }
        }

        return null;
    }

    private function hasNonUniqueSettingsIndex(): bool
    {
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (
                ! ($index['unique'] ?? false)
                && ($index['columns'][0] ?? null) === 'pengaturan_id'
            ) {
                return true;
            }
        }

        return false;
    }
};
