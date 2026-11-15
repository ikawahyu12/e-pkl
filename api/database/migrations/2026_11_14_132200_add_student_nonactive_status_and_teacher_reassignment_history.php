<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORIGINAL_STATUS_TYPE = "enum('aktif','lulus','keluar')";

    private const NONACTIVE_STATUS_TYPE = "enum('aktif','nonaktif','lulus','keluar')";

    private const HISTORY_TABLE = 'riwayat_pergantian_guru';

    public function up(): void
    {
        $this->assertMySql();
        $this->assertStudentStatusTableAvailable();
        $this->assertHistoryTableCompatibleIfPresent();
        if (! Schema::hasTable(self::HISTORY_TABLE)) {
            $this->assertTeacherHistoryParentsAvailable();
        }
        $this->addNonactiveStudentStatus();

        if (! Schema::hasTable(self::HISTORY_TABLE)) {
            $this->createTeacherHistoryTable();
        }

        $this->assertHistoryTableCompatible();
    }

    public function down(): void
    {
        $this->assertMySql();
        $this->assertStudentStatusTableAvailable();
        $this->assertHistoryTableCompatibleIfPresent();

        if (Schema::hasTable(self::HISTORY_TABLE)) {
            throw new RuntimeException(
                'Rollback otomatis ditolak: tabel riwayat pergantian Guru dipertahankan '
                .'agar data audit tidak hilang.'
            );
        }

        $this->restoreOriginalStudentStatus();
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Migration status Siswa dan riwayat pergantian Guru hanya mendukung MySQL.'
            );
        }
    }

    private function assertStudentStatusTableAvailable(): void
    {
        if (! Schema::hasTable('siswa')) {
            throw new RuntimeException(
                'Migration tidak dapat dilanjutkan: tabel epkl.siswa wajib tersedia.'
            );
        }

        if (! Schema::hasColumn('siswa', 'status_siswa')) {
            throw new RuntimeException(
                'Migration tidak dapat dilanjutkan: kolom epkl.siswa.status_siswa wajib tersedia.'
            );
        }
    }

    private function addNonactiveStudentStatus(): void
    {
        $status = $this->studentStatusMetadata();
        $type = $this->normalizeEnumType((string) $status->column_type);
        $this->assertStudentStatusColumnAttributes($status);

        if ($type === self::NONACTIVE_STATUS_TYPE) {
            return;
        }

        if ($type !== self::ORIGINAL_STATUS_TYPE) {
            throw new RuntimeException(
                'Migration ditolak: epkl.siswa.status_siswa memiliki ENUM yang tidak dikenal; '
                .'periksa dan rekonsiliasi skema secara manual.'
            );
        }

        $charset = $this->safeIdentifier((string) $status->character_set);
        $collation = $this->safeIdentifier((string) $status->collation);
        $comment = str_replace("'", "''", (string) $status->comment);

        DB::statement(
            "ALTER TABLE `siswa` MODIFY `status_siswa` "
            ."ENUM('aktif','nonaktif','lulus','keluar') "
            ."CHARACTER SET `$charset` COLLATE `$collation` NOT NULL DEFAULT 'aktif' "
            ."COMMENT '$comment'"
        );
    }

    private function restoreOriginalStudentStatus(): void
    {
        $status = $this->studentStatusMetadata();
        $type = $this->normalizeEnumType((string) $status->column_type);
        $this->assertStudentStatusColumnAttributes($status);

        if ($type === self::ORIGINAL_STATUS_TYPE) {
            return;
        }

        if ($type !== self::NONACTIVE_STATUS_TYPE) {
            throw new RuntimeException(
                'Rollback ditolak: epkl.siswa.status_siswa memiliki ENUM yang tidak dikenal.'
            );
        }

        if (DB::table('siswa')->where('status_siswa', 'nonaktif')->exists()) {
            throw new RuntimeException(
                'Rollback ditolak: nilai status_siswa nonaktif masih digunakan oleh data Siswa.'
            );
        }

        $charset = $this->safeIdentifier((string) $status->character_set);
        $collation = $this->safeIdentifier((string) $status->collation);
        $comment = str_replace("'", "''", (string) $status->comment);

        DB::statement(
            "ALTER TABLE `siswa` MODIFY `status_siswa` "
            ."ENUM('aktif','lulus','keluar') "
            ."CHARACTER SET `$charset` COLLATE `$collation` NOT NULL DEFAULT 'aktif' "
            ."COMMENT '$comment'"
        );
    }

    private function studentStatusMetadata(): object
    {
        $status = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS nullable, '
                .'COLUMN_DEFAULT AS default_value, CHARACTER_SET_NAME AS character_set, '
                .'COLLATION_NAME AS collation, COLUMN_COMMENT AS comment, EXTRA AS extra, '
                .'GENERATION_EXPRESSION AS generation_expression '
                .'FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['siswa', 'status_siswa']
        );

        if (! $status) {
            throw new RuntimeException(
                'Migration tidak dapat melanjutkan: metadata epkl.siswa.status_siswa tidak ditemukan.'
            );
        }

        return $status;
    }

    private function assertStudentStatusColumnAttributes(object $status): void
    {
        if (
            strtoupper((string) $status->nullable) !== 'NO'
            || trim((string) $status->default_value, "'\"") !== 'aktif'
            || ! is_string($status->character_set)
            || ! is_string($status->collation)
            || ! is_string($status->comment)
            || str_contains($status->comment, '\\')
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $status->comment) === 1
            || trim((string) $status->extra) !== ''
            || trim((string) $status->generation_expression) !== ''
        ) {
            throw new RuntimeException(
                'Migration ditolak: atribut nullability/default/charset/collation/generated '
                .'pada epkl.siswa.status_siswa tidak sesuai dan tidak dapat dipertahankan dengan aman.'
            );
        }

        $this->safeIdentifier((string) $status->character_set);
        $this->safeIdentifier((string) $status->collation);
    }

    private function normalizeEnumType(string $type): string
    {
        return strtolower(preg_replace('/\s*,\s*/', ',', trim($type)));
    }

    private function safeIdentifier(string $identifier): string
    {
        if (preg_match('/^[a-z0-9_]+$/i', $identifier) !== 1) {
            throw new RuntimeException(
                'Migration ditolak: metadata charset/collation status Siswa tidak valid.'
            );
        }

        return $identifier;
    }

    private function assertHistoryTableCompatibleIfPresent(): void
    {
        if (Schema::hasTable(self::HISTORY_TABLE)) {
            $this->assertHistoryTableCompatible();
        }
    }

    private function assertHistoryTableCompatible(): void
    {
        $expectedColumns = [
            'id' => ['bigint unsigned', false, true],
            'penempatan_id' => ['bigint unsigned', false, false],
            'guru_lama_id' => ['bigint unsigned', false, false],
            'guru_baru_id' => ['bigint unsigned', false, false],
            'admin_user_id' => ['bigint unsigned', false, false],
            'alasan' => ['text', false, false],
            'created_at' => ['timestamp', false, false],
        ];
        $columns = collect(Schema::getColumns(self::HISTORY_TABLE))->keyBy('name');
        $errors = [];

        if ($columns->keys()->sort()->values()->all() !== collect(array_keys($expectedColumns))->sort()->values()->all()) {
            $errors[] = 'daftar kolom tidak sama dengan struktur target';
        }

        foreach ($expectedColumns as $name => [$type, $nullable, $autoIncrement]) {
            $column = $columns->get($name);
            if (
                ! $column
                || strtolower($column['type']) !== $type
                || $column['nullable'] !== $nullable
                || $column['auto_increment'] !== $autoIncrement
            ) {
                $errors[] = "kolom $name tidak sesuai";
            }
        }

        $stringCharset = null;
        $stringCollation = null;
        $stringMetadata = collect(DB::select(
            'SELECT COLUMN_NAME AS name, CHARACTER_SET_NAME AS charset_name, '
                .'COLLATION_NAME AS collation_name FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::HISTORY_TABLE]
        ))->keyBy('name');
        foreach (['alasan'] as $name) {
            $metadata = $stringMetadata->get($name);
            $charset = $metadata->charset_name ?? null;
            $collation = $metadata->collation_name ?? null;
            if (! is_string($charset) || $charset === '' || ! is_string($collation) || $collation === '') {
                $errors[] = "kolom $name tidak memiliki charset/collation yang valid";
            } elseif (
                ($stringCharset !== null && $stringCharset !== $charset)
                || ($stringCollation !== null && $stringCollation !== $collation)
            ) {
                $errors[] = 'charset/collation kolom teks tidak konsisten';
            } else {
                $stringCharset = $charset;
                $stringCollation = $collation;
            }
        }

        $expectedIndexes = [
            ['primary', ['id'], true, true],
            ['riwayat_pergantian_guru_penempatan_id_created_at_index', ['penempatan_id', 'created_at'], false, false],
            ['idx_riwayat_guru_lama_waktu', ['guru_lama_id', 'created_at'], false, false],
            ['idx_riwayat_guru_baru_waktu', ['guru_baru_id', 'created_at'], false, false],
        ];
        $indexes = Schema::getIndexes(self::HISTORY_TABLE);
        $validIndexTypes = ['BTREE', 'FULLTEXT', 'SPATIAL', 'HASH'];
        foreach ($indexes as $index) {
            if (
                $index['name'] === ''
                || $index['columns'] === []
                || ! in_array(strtoupper($index['type']), $validIndexTypes, true)
            ) {
                $errors[] = 'terdapat definisi index yang tidak valid';
            }

            foreach ($index['columns'] as $column) {
                if (! $columns->has($column)) {
                    $errors[] = "index {$index['name']} merujuk kolom yang tidak tersedia";
                    break;
                }
            }
        }

        foreach ($expectedIndexes as [$name, $indexColumns, $unique, $primary]) {
            if (! $this->hasIndex($indexes, $name, $indexColumns, $unique, $primary)) {
                $errors[] = "index $name tidak sesuai";
            }
        }

        $expectedForeignKeys = [
            ['penempatan_id', 'penempatan_pkl'],
            ['guru_lama_id', 'guru'],
            ['guru_baru_id', 'guru'],
            ['admin_user_id', 'users'],
        ];
        $foreignKeys = Schema::getForeignKeys(self::HISTORY_TABLE);
        foreach ($expectedForeignKeys as [$column, $table]) {
            if (! $this->hasForeignKey($foreignKeys, $column, $table)) {
                $errors[] = "foreign key $column ke $table.id tidak sesuai";
            }
        }
        if (count($foreignKeys) !== count($expectedForeignKeys)) {
            $errors[] = 'terdapat foreign key tambahan yang tidak didefinisikan migration';
        }

        $engine = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::HISTORY_TABLE]
        )?->engine;
        if (strtolower((string) $engine) !== 'innodb') {
            $errors[] = 'engine tabel bukan InnoDB';
        }

        if ($errors !== []) {
            throw new RuntimeException(
                'Skema epkl.riwayat_pergantian_guru tidak cocok atau parsial: '
                .implode('; ', $errors).'. Rekonsiliasi diperlukan sebelum migration dilanjutkan.'
            );
        }
    }

    private function assertTeacherHistoryParentsAvailable(): void
    {
        foreach (['penempatan_pkl', 'guru', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Migration ditolak sebelum DDL: tabel induk epkl.$table wajib tersedia."
                );
            }

            $id = collect(Schema::getColumns($table))->firstWhere('name', 'id');
            $hasPrimaryId = collect(Schema::getIndexes($table))->contains(
                fn (array $index): bool => $index['name'] === 'primary'
                    && $index['columns'] === ['id']
                    && $index['unique']
                    && $index['primary']
                    && strtoupper($index['type']) === 'BTREE'
            );
            $engine = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES '
                    .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            )?->engine;
            if (
                ! $id
                || strtolower($id['type']) !== 'bigint unsigned'
                || ! $hasPrimaryId
                || strtolower((string) $engine) !== 'innodb'
            ) {
                throw new RuntimeException(
                    "Migration ditolak sebelum DDL: epkl.$table.id harus bigint unsigned "
                    .'dan tabel induk harus menggunakan InnoDB.'
                );
            }
        }
    }

    private function hasIndex(
        array $indexes,
        string $name,
        array $columns,
        bool $unique,
        bool $primary
    ): bool {
        foreach ($indexes as $index) {
            if (
                $index['name'] === $name
                && $index['columns'] === $columns
                && $index['unique'] === $unique
                && $index['primary'] === $primary
                && strtoupper($index['type']) === 'BTREE'
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasForeignKey(array $foreignKeys, string $column, string $table): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        foreach ($foreignKeys as $foreignKey) {
            if (
                $foreignKey['columns'] === [$column]
                && $foreignKey['foreign_schema'] === $database
                && $foreignKey['foreign_table'] === $table
                && $foreignKey['foreign_columns'] === ['id']
                && $this->normalizeDeleteRule($foreignKey['on_delete']) === 'restrict'
                && $this->normalizeDeleteRule($foreignKey['on_update']) === 'cascade'
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDeleteRule(?string $rule): string
    {
        $rule = strtolower(trim((string) $rule));

        return $rule === 'no action' ? 'restrict' : $rule;
    }

    private function createTeacherHistoryTable(): void
    {
        Schema::create(self::HISTORY_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('penempatan_id')
                ->constrained('penempatan_pkl')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('guru_lama_id')
                ->constrained('guru')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('guru_baru_id')
                ->constrained('guru')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('admin_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->text('alasan');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['penempatan_id', 'created_at'],
                'riwayat_pergantian_guru_penempatan_id_created_at_index'
            );
            $table->index(['guru_lama_id', 'created_at'], 'idx_riwayat_guru_lama_waktu');
            $table->index(['guru_baru_id', 'created_at'], 'idx_riwayat_guru_baru_waktu');
        });
    }
};
