<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'kuota_instansi_periode';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            $this->assertTableCompatible();

            return;
        }

        $this->assertParentsAvailable();

        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instansi_id')
                ->constrained('instansi')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('periode_id')
                ->constrained('periode_pkl')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // NULL berarti kuota tidak dibatasi.
            $table->unsignedInteger('kuota_siswa')->nullable();
            $table->timestamps();
            $table->unique(['instansi_id', 'periode_id'], 'uq_kuota_instansi_periode');
        });

        $this->assertTableCompatible();
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $this->assertTableCompatible();
        throw new RuntimeException(
            'Rollback otomatis ditolak: konfigurasi kuota instansi per periode dipertahankan '
            .'agar perubahan konfigurasi tidak hilang.'
        );
    }

    private function assertTableCompatible(): void
    {
        $expectedColumns = [
            'id' => ['bigint unsigned', false, null, true],
            'instansi_id' => ['bigint unsigned', false, null, false],
            'periode_id' => ['bigint unsigned', false, null, false],
            'kuota_siswa' => ['int unsigned', true, null, false],
            'created_at' => ['timestamp', true, null, false],
            'updated_at' => ['timestamp', true, null, false],
        ];
        $columns = collect(Schema::getColumns(self::TABLE))->keyBy('name');
        $errors = [];

        if ($columns->keys()->sort()->values()->all() !== collect(array_keys($expectedColumns))->sort()->values()->all()) {
            $errors[] = 'daftar kolom tidak sama dengan struktur target';
        }

        foreach ($expectedColumns as $name => [$type, $nullable, $default, $autoIncrement]) {
            $column = $columns->get($name);
            $actualDefault = $column['default'] ?? null;
            if ($actualDefault !== null) {
                $actualDefault = strtolower(trim((string) $actualDefault, "'\""));
                if (preg_match('/^(current_timestamp|now)\s*(?:\(\s*\d*\s*\))?$/i', $actualDefault) === 1) {
                    $actualDefault = 'current_timestamp';
                }
            }

            if (
                ! $column
                || strtolower($column['type']) !== $type
                || $column['nullable'] !== $nullable
                || $actualDefault !== $default
                || $column['auto_increment'] !== $autoIncrement
            ) {
                $errors[] = "kolom $name tidak sesuai";
            }
        }

        $indexes = Schema::getIndexes(self::TABLE);
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

        foreach ([
            ['primary', ['id'], true, true],
            ['uq_kuota_instansi_periode', ['instansi_id', 'periode_id'], true, false],
        ] as [$name, $indexColumns, $unique, $primary]) {
            if (! $this->hasIndex($indexes, $name, $indexColumns, $unique, $primary)) {
                $errors[] = "index $name tidak sesuai";
            }
        }

        $foreignKeys = Schema::getForeignKeys(self::TABLE);
        $expectedForeignKeys = [
            ['instansi_id', 'instansi'],
            ['periode_id', 'periode_pkl'],
        ];
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
            [self::TABLE]
        )?->engine;
        if (strtolower((string) $engine) !== 'innodb') {
            $errors[] = 'engine tabel bukan InnoDB';
        }

        if ($errors !== []) {
            throw new RuntimeException(
                'Skema epkl.'.self::TABLE.' tidak cocok atau parsial: '
                .implode('; ', $errors).'. Rekonsiliasi diperlukan.'
            );
        }
    }

    private function assertParentsAvailable(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Migration kuota instansi per periode hanya mendukung MySQL.'
            );
        }

        foreach (['instansi', 'periode_pkl'] as $table) {
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
                && $this->normalizeRule($foreignKey['on_delete']) === 'restrict'
                && $this->normalizeRule($foreignKey['on_update']) === 'cascade'
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRule(?string $rule): string
    {
        $rule = strtolower(trim((string) $rule));

        return $rule === 'no action' ? 'restrict' : $rule;
    }
};
