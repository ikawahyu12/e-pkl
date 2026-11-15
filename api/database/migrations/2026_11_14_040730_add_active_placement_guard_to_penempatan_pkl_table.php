<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'penempatan_pkl';

    private const OWNER_MARKER = 'e-pkl:2026_11_14_040730_active_placement_guard';

    private const UNIQUE_INDEX = 'uq_penempatan_siswa_periode_aktif';

    private const PERIOD_INDEX = 'idx_penempatan_instansi_periode_status';

    private const UNIQUE_INDEX_OWNER = 'e-pkl:2026_11_14_040730:unique';

    private const PERIOD_INDEX_OWNER = 'e-pkl:2026_11_14_040730:period';

    public function up(): void
    {
        $this->assertPrerequisites();

        $guardExists = Schema::hasColumn(self::TABLE, 'aktif_guard');
        $indexes = Schema::getIndexes(self::TABLE);
        $uniqueIndex = $this->findIndex($indexes, self::UNIQUE_INDEX);
        $periodIndex = $this->findIndex($indexes, self::PERIOD_INDEX);

        if (! $guardExists && ($uniqueIndex || $periodIndex)) {
            throw new RuntimeException(
                'Migration ditolak: index guard sudah tersedia tanpa kolom aktif_guard; '
                .'periksa dan rekonsiliasi skema parsial secara manual.'
            );
        }

        if ($guardExists) {
            $this->assertOwnedGuardColumn();
        }

        $this->assertIndexDefinition(
            $uniqueIndex,
            self::UNIQUE_INDEX,
            ['siswa_id', 'periode_id', 'aktif_guard'],
            true
        );
        $this->assertIndexDefinition(
            $periodIndex,
            self::PERIOD_INDEX,
            ['instansi_id', 'periode_id', 'status'],
            false
        );
        $this->assertNoEquivalentAlternateIndexes($indexes);

        $this->assertIndexComment($uniqueIndex, self::UNIQUE_INDEX, self::UNIQUE_INDEX_OWNER);
        $this->assertIndexComment($periodIndex, self::PERIOD_INDEX, self::PERIOD_INDEX_OWNER);

        if (! $uniqueIndex) {
            $this->assertNoActivePlacementDuplicates();
        }

        if (! $guardExists) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->tinyInteger('aktif_guard')
                    ->nullable()
                    ->storedAs("CASE WHEN status IN ('belum_mulai', 'aktif') THEN 1 ELSE NULL END")
                    ->comment(self::OWNER_MARKER);
            });
        }

        if (! $uniqueIndex) {
            DB::statement(
                'ALTER TABLE `'.self::TABLE.'` ADD UNIQUE INDEX `'.self::UNIQUE_INDEX.'` '
                .'(`siswa_id`, `periode_id`, `aktif_guard`) COMMENT \''.self::UNIQUE_INDEX_OWNER.'\''
            );
        }

        if (! $periodIndex) {
            DB::statement(
                'ALTER TABLE `'.self::TABLE.'` ADD INDEX `'.self::PERIOD_INDEX.'` '
                .'(`instansi_id`, `periode_id`, `status`) COMMENT \''.self::PERIOD_INDEX_OWNER.'\''
            );
        }

        $this->assertOwnedGuardColumn();
        $indexes = Schema::getIndexes(self::TABLE);
        $uniqueIndex = $this->findIndex($indexes, self::UNIQUE_INDEX);
        $periodIndex = $this->findIndex($indexes, self::PERIOD_INDEX);

        $this->assertIndexDefinition(
            $uniqueIndex,
            self::UNIQUE_INDEX,
            ['siswa_id', 'periode_id', 'aktif_guard'],
            true
        );
        $this->assertIndexDefinition(
            $periodIndex,
            self::PERIOD_INDEX,
            ['instansi_id', 'periode_id', 'status'],
            false
        );
        $this->assertIndexComment($uniqueIndex, self::UNIQUE_INDEX, self::UNIQUE_INDEX_OWNER);
        $this->assertIndexComment($periodIndex, self::PERIOD_INDEX, self::PERIOD_INDEX_OWNER);

        if (! $uniqueIndex || ! $periodIndex) {
            throw new RuntimeException(
                'Migration gagal memverifikasi kedua index setelah DDL; '
                .'skema dipertahankan untuk rekonsiliasi.'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $guardExists = Schema::hasColumn(self::TABLE, 'aktif_guard');
        $indexes = Schema::getIndexes(self::TABLE);
        $uniqueIndex = $this->findIndex($indexes, self::UNIQUE_INDEX);
        $periodIndex = $this->findIndex($indexes, self::PERIOD_INDEX);

        if (! $guardExists) {
            if ($uniqueIndex || $periodIndex) {
                throw new RuntimeException(
                    'Rollback ditolak: ditemukan index guard tanpa kolom aktif_guard yang dimiliki migration.'
                );
            }

            return;
        }

        if (! $uniqueIndex || ! $periodIndex) {
            throw new RuntimeException(
                'Rollback ditolak: kedua index migration harus tersedia sebelum rollback; '
                .'periksa skema parsial dan jalankan ulang migration terlebih dahulu.'
            );
        }

        $this->assertOwnedGuardColumn();
        $this->assertIndexDefinition(
            $uniqueIndex,
            self::UNIQUE_INDEX,
            ['siswa_id', 'periode_id', 'aktif_guard'],
            true
        );
        $this->assertIndexDefinition(
            $periodIndex,
            self::PERIOD_INDEX,
            ['instansi_id', 'periode_id', 'status'],
            false
        );
        $this->assertIndexComment($uniqueIndex, self::UNIQUE_INDEX, self::UNIQUE_INDEX_OWNER);
        $this->assertIndexComment($periodIndex, self::PERIOD_INDEX, self::PERIOD_INDEX_OWNER);
        $this->assertNoEquivalentAlternateIndexes($indexes);

        foreach ($indexes as $index) {
            if (
                in_array('aktif_guard', $index['columns'], true)
                && $index['name'] !== self::UNIQUE_INDEX
            ) {
                throw new RuntimeException(
                    'Rollback ditolak: terdapat index tambahan yang memakai aktif_guard; '
                    .'kolom guard tidak dapat dihapus dengan aman.'
                );
            }
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique(self::UNIQUE_INDEX);
            $table->dropIndex(self::PERIOD_INDEX);
            $table->dropColumn('aktif_guard');
        });
    }

    private function assertPrerequisites(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Migration guard penempatan hanya mendukung MySQL.'
            );
        }

        if (! Schema::hasTable(self::TABLE)) {
            throw new RuntimeException(
                'Migration ditolak: tabel epkl.penempatan_pkl wajib tersedia.'
            );
        }

        $columns = collect(Schema::getColumns(self::TABLE))->keyBy('name');
        foreach ([
            'siswa_id' => 'bigint unsigned',
            'periode_id' => 'bigint unsigned',
            'instansi_id' => 'bigint unsigned',
            'status' => "enum('belum_mulai','aktif','selesai','dibatalkan')",
        ] as $name => $type) {
            $column = $columns->get($name);
            if (! $column || strtolower($column['type']) !== $type || $column['nullable']) {
                throw new RuntimeException(
                    "Migration ditolak: epkl.".self::TABLE.".$name harus bertipe $type NOT NULL."
                );
            }
        }

        $engine = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE]
        )?->engine;
        if (strtolower((string) $engine) !== 'innodb') {
            throw new RuntimeException(
                'Migration ditolak: epkl.penempatan_pkl harus menggunakan InnoDB.'
            );
        }
    }

    private function assertNoActivePlacementDuplicates(): void
    {
        $hasDuplicate = DB::table(self::TABLE)
            ->select('siswa_id', 'periode_id')
            ->whereIn('status', ['belum_mulai', 'aktif'])
            ->groupBy('siswa_id', 'periode_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicate) {
            throw new RuntimeException(
                'Migration ditolak: terdapat lebih dari satu penempatan belum_mulai/aktif '
                .'untuk Siswa dan periode yang sama.'
            );
        }
    }

    private function assertOwnedGuardColumn(): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS nullable, '
                .'GENERATION_EXPRESSION AS expression, COLUMN_COMMENT AS comment, EXTRA AS extra '
                .'FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLE, 'aktif_guard']
        );

        $extra = strtolower(trim((string) ($column->extra ?? '')));

        if (
            ! $column
            || strtolower((string) $column->column_type) !== 'tinyint'
            || strtoupper((string) $column->nullable) !== 'YES'
            || ! $this->isExpectedGuardExpression((string) $column->expression)
            || (string) $column->comment !== self::OWNER_MARKER
            || ! str_contains($extra, 'stored generated')
        ) {
            throw new RuntimeException(
                'Migration ditolak: kolom aktif_guard sudah ada tetapi bukan generated guard '
                .'yang dibuat migration ini atau definisinya tidak cocok.'
            );
        }
    }

    private function isExpectedGuardExpression(string $expression): bool
    {
        $stripOuterParentheses = static function (string $value): string {
            $value = trim($value);

            while (str_starts_with($value, '(') && str_ends_with($value, ')')) {
                $depth = 0;
                $inString = false;
                $outerPairWrapsValue = true;
                $length = strlen($value);

                for ($i = 0; $i < $length; $i++) {
                    $character = $value[$i];

                    if ($inString) {
                        if ($character === '\\') {
                            $i++;
                        } elseif ($character === "'") {
                            if (($value[$i + 1] ?? null) === "'") {
                                $i++;
                            } else {
                                $inString = false;
                            }
                        }

                        continue;
                    }

                    if ($character === '\\') {
                        $i++;
                    } elseif ($character === "'") {
                        $inString = true;
                    } elseif ($character === '(') {
                        $depth++;
                    } elseif ($character === ')') {
                        $depth--;
                        if ($depth === 0 && $i !== $length - 1) {
                            $outerPairWrapsValue = false;
                            break;
                        }
                    }
                }

                if (! $outerPairWrapsValue || $depth !== 0 || $inString) {
                    break;
                }

                $value = trim(substr($value, 1, -1));
            }

            return $value;
        };

        $expression = $stripOuterParentheses($expression);

        if (
            preg_match(
                '~(?i:^\s*case\s+when\s+)(?<condition>.*?)(?i:\s+then\s+1\s+else\s+null\s+end\s*$)~s',
                $expression,
                $matches
            ) !== 1
        ) {
            return false;
        }

        $condition = $stripOuterParentheses($matches['condition']);

        return preg_match(
            '~(?i:^\s*`?status`?\s+in\s*\(\s*(?:_utf8mb4\s*)?)'
                .'\\\\?\'belum_mulai\\\\?\''
                .'(?i:\s*,\s*(?:_utf8mb4\s*)?)'
                .'\\\\?\'aktif\\\\?\''
                .'(?i:\s*\)\s*$)~',
            $condition
        ) === 1;
    }

    private function assertIndexComment(?array $index, string $name, string $owner): void
    {
        if ($index === null) {
            return;
        }

        $comment = $this->indexComment($name);
        if ($comment !== $owner) {
            throw new RuntimeException(
                "Migration ditolak: index $name tidak memiliki marker kepemilikan yang tepat; "
                .'rekonsiliasi skema diperlukan.'
            );
        }
    }

    private function indexComment(string $name): string
    {
        $comments = DB::select(
            'SELECT INDEX_COMMENT AS index_comment FROM information_schema.STATISTICS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [self::TABLE, $name]
        );
        if ($comments === []) {
            return '';
        }

        $comment = $comments[0]->index_comment;
        foreach ($comments as $row) {
            if ($row->index_comment !== $comment) {
                throw new RuntimeException(
                    "Migration ditolak: baris metadata untuk index $name memiliki komentar berbeda."
                );
            }
        }

        return (string) $comment;
    }

    private function findIndex(array $indexes, string $name): ?array
    {
        foreach ($indexes as $index) {
            if ($index['name'] === $name) {
                return $index;
            }
        }

        return null;
    }

    private function assertNoEquivalentAlternateIndexes(array $indexes): void
    {
        $expected = [
            self::UNIQUE_INDEX => [
                'columns' => ['siswa_id', 'periode_id', 'aktif_guard'],
                'unique' => true,
            ],
            self::PERIOD_INDEX => [
                'columns' => ['instansi_id', 'periode_id', 'status'],
                'unique' => false,
            ],
        ];

        foreach ($indexes as $index) {
            foreach ($expected as $name => $definition) {
                $indexColumns = $index['columns'] ?? [];
                $expectedColumns = $definition['columns'];
                sort($indexColumns);
                sort($expectedColumns);

                if (
                    ($index['name'] ?? null) !== $name
                    && $indexColumns === $expectedColumns
                    && ($index['unique'] ?? null) === $definition['unique']
                ) {
                    throw new RuntimeException(
                        "Migration ditolak: index alternatif {$index['name']} memiliki kumpulan kolom "
                        .implode(', ', $definition['columns']).' dan sifat unique yang setara; '
                        .'rekonsiliasi diperlukan.'
                    );
                }
            }
        }
    }

    private function assertIndexDefinition(
        ?array $index,
        string $name,
        array $columns,
        bool $unique
    ): void {
        if (
            $index !== null
            && (
                $index['columns'] !== $columns
                || $index['unique'] !== $unique
                || $index['primary']
                || strtoupper($index['type']) !== 'BTREE'
            )
        ) {
            throw new RuntimeException(
                "Migration ditolak: index $name sudah ada dengan definisi yang tidak sesuai."
            );
        }
    }
};
