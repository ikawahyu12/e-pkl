<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AGENDA = 'agenda_kunjungan';

    private const PIVOT = 'agenda_kunjungan_siswa';

    private const AGENDA_COMMENT = 'e-pkl:2026_11_15_140001:agenda';

    private const PIVOT_COMMENT = 'e-pkl:2026_11_15_140001:pivot';

    public function up(): void
    {
        $this->assertParentTables();

        $hasAgenda = Schema::hasTable(self::AGENDA);
        $hasPivot = Schema::hasTable(self::PIVOT);
        if (! $hasAgenda && ! $hasPivot) {
            $this->createTables();
            $this->assertCompleteSchema();

            return;
        }

        if ($hasAgenda !== $hasPivot && ! $this->canResumeAgendaCreation($hasAgenda, $hasPivot)) {
            throw new RuntimeException(
                'Ditemukan skema agenda parsial; rekonsiliasi manual diperlukan sebelum migration.'
            );
        }

        if ($hasAgenda) {
            $this->assertExistingTables($hasPivot);
            $this->assertNoLegacyCanceledRows();
            $this->assertNoOrphanReferences($hasPivot);
        }

        if ($hasAgenda && ! $hasPivot) {
            $this->createPivotTable();
        }

        $this->assertExistingTables();
        $this->assertExistingForeignKeys();
        $this->assertNoOrphanReferences();
        $this->addAgendaMetadataColumns();
        $this->addMissingIndexes();
        $this->addMissingForeignKeys();
        $this->assertCompleteSchema();
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback otomatis ditolak agar agenda kunjungan dan histori pembatalan tetap tersimpan.'
        );
    }

    private function assertParentTables(): void
    {
        foreach (['guru', 'instansi', 'siswa', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Tabel referensi $table.id wajib tersedia.");
            }

            $this->assertInnoDb($table);
            $this->assertColumn($table, 'id', 'bigint unsigned', false, null, true);
            $this->assertPrimaryKey($table, ['id']);
        }
    }

    private function createTables(): void
    {
        Schema::create(self::AGENDA, function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment(self::AGENDA_COMMENT);
            $table->id();
            $table->foreignId('guru_id')
                ->constrained('guru', 'id')
                ->restrictOnDelete();
            $table->foreignId('instansi_id')
                ->constrained('instansi', 'id')
                ->restrictOnDelete();
            $table->index('guru_id', 'idx_agenda_kunjungan_guru');
            $table->index('instansi_id', 'idx_agenda_kunjungan_instansi');
            $table->date('tanggal')->index();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('jenis_pencatatan', 20)->nullable();
            $table->string('status', 20)->default('terjadwal');
            $table->text('catatan')->nullable();
            $table->text('alasan_pembatalan')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_oleh_user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->restrictOnDelete();
            $table->timestamps();
        });

        $this->createPivotTable();
    }

    private function createPivotTable(): void
    {
        Schema::create(self::PIVOT, function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment(self::PIVOT_COMMENT);
            $table->unsignedBigInteger('agenda_kunjungan_id');
            $table->unsignedBigInteger('siswa_id');
            $table->primary(['agenda_kunjungan_id', 'siswa_id']);
            $table->index('siswa_id', 'idx_agenda_kunjungan_siswa_siswa');
            $table->foreign('agenda_kunjungan_id', 'fk_agenda_siswa_agenda')
                ->references('id')->on(self::AGENDA)
                ->restrictOnDelete();
            $table->foreign('siswa_id', 'fk_agenda_siswa_siswa')
                ->references('id')->on('siswa')
                ->restrictOnDelete();
        });
    }

    private function canResumeAgendaCreation(bool $hasAgenda, bool $hasPivot): bool
    {
        if (! $hasAgenda || $hasPivot) {
            return false;
        }

        if ($this->tableComment(self::AGENDA) !== self::AGENDA_COMMENT) {
            return false;
        }

        $this->assertExistingTables(false);

        return true;
    }

    private function assertExistingTables(bool $requirePivot = true): void
    {
        $expected = [
            self::AGENDA => [
                'id' => ['bigint unsigned', false, null, true],
                'guru_id' => ['bigint unsigned', false, null, false],
                'instansi_id' => ['bigint unsigned', false, null, false],
                'tanggal' => ['date', false, null, false],
                'jam_mulai' => ['time', false, null, false],
                'jam_selesai' => ['time', false, null, false],
                'status' => ['varchar(20)', false, 'terjadwal', false],
                'catatan' => ['text', true, null, false],
                'created_at' => ['timestamp', true, null, false],
                'updated_at' => ['timestamp', true, null, false],
            ],
            self::PIVOT => [
                'agenda_kunjungan_id' => ['bigint unsigned', false, null, false],
                'siswa_id' => ['bigint unsigned', false, null, false],
            ],
        ];

        foreach ($expected as $table => $columns) {
            if ($table === self::PIVOT && ! $requirePivot) {
                continue;
            }

            $this->assertInnoDb($table);
            foreach ($columns as $name => [$type, $nullable, $default, $autoIncrement]) {
                $this->assertColumn($table, $name, $type, $nullable, $default, $autoIncrement);
            }

            if ($table === self::AGENDA) {
                foreach ([
                    'alasan_pembatalan' => 'text',
                    'dibatalkan_at' => 'timestamp',
                    'dibatalkan_oleh_user_id' => 'bigint unsigned',
                    'jenis_pencatatan' => 'varchar(20)',
                ] as $name => $type) {
                    if (Schema::hasColumn($table, $name)) {
                        $this->assertColumn($table, $name, $type, true, null, false);
                    }
                }
            }
        }

        $this->assertPrimaryKey(self::AGENDA, ['id']);
        $this->assertCompatibleIndex(self::AGENDA, ['guru_id'], false);
        $this->assertCompatibleIndex(self::AGENDA, ['instansi_id'], false);
        $this->assertCompatibleIndex(self::AGENDA, ['tanggal'], false);
        $this->assertIndexNameCompatibility(self::AGENDA, ['guru_id'], 'idx_agenda_kunjungan_guru');
        $this->assertIndexNameCompatibility(self::AGENDA, ['instansi_id'], 'idx_agenda_kunjungan_instansi');
        $this->assertIndexNameCompatibility(self::AGENDA, ['tanggal'], 'idx_agenda_kunjungan_tanggal');

        if ($requirePivot) {
            $this->assertPrimaryKey(self::PIVOT, ['agenda_kunjungan_id', 'siswa_id']);
            $this->assertCompatibleIndex(self::PIVOT, ['siswa_id'], false);
            $this->assertIndexNameCompatibility(
                self::PIVOT,
                ['siswa_id'],
                'idx_agenda_kunjungan_siswa_siswa'
            );
        }

        $this->assertForeignKeyDefinitions($requirePivot);
        $this->assertForeignKeyNameCompatibility($requirePivot);
    }

    private function assertCompleteSchema(): void
    {
        $this->assertExistingTables();
        $this->assertColumn(self::AGENDA, 'alasan_pembatalan', 'text', true, null, false);
        $this->assertColumn(self::AGENDA, 'dibatalkan_at', 'timestamp', true, null, false);
        $this->assertColumn(self::AGENDA, 'dibatalkan_oleh_user_id', 'bigint unsigned', true, null, false);
        $this->assertColumn(self::AGENDA, 'jenis_pencatatan', 'varchar(20)', true, null, false);
        $this->assertIndex(self::AGENDA, ['guru_id'], false);
        $this->assertIndex(self::AGENDA, ['instansi_id'], false);
        $this->assertIndex(self::AGENDA, ['tanggal'], false);
        $this->assertIndex(self::PIVOT, ['siswa_id'], false);

        foreach ([
            [self::AGENDA, ['guru_id'], 'guru', 'restrict'],
            [self::AGENDA, ['instansi_id'], 'instansi', 'restrict'],
            [self::AGENDA, ['dibatalkan_oleh_user_id'], 'users', 'restrict'],
            [self::PIVOT, ['agenda_kunjungan_id'], self::AGENDA, 'restrict'],
            [self::PIVOT, ['siswa_id'], 'siswa', 'restrict'],
        ] as [$table, $columns, $parent, $onDelete]) {
            if ($columns === ['dibatalkan_oleh_user_id']
                && ! Schema::hasColumn(self::AGENDA, 'dibatalkan_oleh_user_id')) {
                throw new RuntimeException('Metadata FK pembatal agenda belum lengkap.');
            }

            $foreignKey = $this->findForeignKey($table, $columns);
            if (! $foreignKey
                || strtolower((string) $foreignKey['foreign_table']) !== $parent
                || $foreignKey['foreign_columns'] !== ['id']
                || ! in_array(strtolower((string) $foreignKey['on_delete']), [$onDelete, 'no action'], true)
                || ! in_array(strtolower((string) $foreignKey['on_update']), ['restrict', 'no action'], true)) {
                throw new RuntimeException(
                    "Foreign key target tidak lengkap atau tidak sesuai pada $table.".implode(',', $columns).'.'
                );
            }
        }
    }

    private function assertColumn(
        string $table,
        string $name,
        string $type,
        bool $nullable,
        ?string $default,
        bool $autoIncrement
    ): void {
        $column = collect(Schema::getColumns($table))->firstWhere('name', $name);
        if (! $column
            || strtolower((string) ($column['type'] ?? '')) !== $type
            || (bool) ($column['nullable'] ?? false) !== $nullable
            || $this->normalizeDefault($column['default'] ?? null) !== $this->normalizeDefault($default)
            || (bool) ($column['auto_increment'] ?? false) !== $autoIncrement) {
            throw new RuntimeException(
                "$table.$name tidak sesuai tipe, nullability, default, atau auto-increment DDL target."
            );
        }
    }

    private function normalizeDefault(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim((string) $value, " \t\n\r\0\x0B'\""));

        return match ($value) {
            'current_timestamp()' => 'current_timestamp',
            default => $value,
        };
    }

    private function assertInnoDb(string $table): void
    {
        $metadata = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        if (strtolower((string) ($metadata->engine ?? '')) !== 'innodb') {
            throw new RuntimeException("$table harus menggunakan engine InnoDB.");
        }
    }

    private function tableComment(string $table): string
    {
        $metadata = DB::selectOne(
            'SELECT TABLE_COMMENT AS table_comment FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        return (string) ($metadata->table_comment ?? '');
    }

    private function assertPrimaryKey(string $table, array $columns): void
    {
        $primary = collect(Schema::getIndexes($table))
            ->first(fn (array $index): bool => (bool) ($index['primary'] ?? false));

        if (! $primary || $primary['columns'] !== $columns) {
            throw new RuntimeException("$table memiliki primary key yang tidak sesuai DDL target.");
        }
    }

    private function assertIndex(string $table, array $columns, bool $unique): void
    {
        $exists = collect(Schema::getIndexes($table))->contains(
            fn (array $index): bool => $index['columns'] === $columns
                && (bool) ($index['unique'] ?? false) === $unique
                && ! ($index['primary'] ?? false)
        );

        if (! $exists) {
            throw new RuntimeException(
                "$table memerlukan ".($unique ? 'unique ' : '').'index pada '.implode(',', $columns).'.'
            );
        }
    }

    private function assertCompatibleIndex(string $table, array $columns, bool $unique): void
    {
        $conflicting = collect(Schema::getIndexes($table))->first(
            fn (array $index): bool => $index['columns'] === $columns
                && (bool) ($index['unique'] ?? false) !== $unique
                && ! ($index['primary'] ?? false)
        );

        if ($conflicting) {
            throw new RuntimeException(
                "$table memiliki index dengan definisi yang bertentangan pada ".implode(',', $columns).'.'
            );
        }
    }

    private function assertIndexNameCompatibility(string $table, array $columns, string $name): void
    {
        $namedIndex = collect(Schema::getIndexes($table))->first(
            fn (array $index): bool => $index['name'] === $name
        );

        if ($namedIndex && $namedIndex['columns'] !== $columns) {
            throw new RuntimeException("$table index $name sudah dipakai dengan kolom lain.");
        }
    }

    private function assertForeignKeyDefinitions(bool $includePivot = true): void
    {
        $expected = [
            [self::AGENDA, ['guru_id'], 'guru', 'restrict'],
            [self::AGENDA, ['instansi_id'], 'instansi', 'restrict'],
        ];
        if (Schema::hasColumn(self::AGENDA, 'dibatalkan_oleh_user_id')) {
            $expected[] = [self::AGENDA, ['dibatalkan_oleh_user_id'], 'users', 'restrict'];
        }
        if ($includePivot) {
            $expected[] = [self::PIVOT, ['agenda_kunjungan_id'], self::AGENDA, 'restrict'];
            $expected[] = [self::PIVOT, ['siswa_id'], 'siswa', 'restrict'];
        }

        foreach ($expected as [$table, $columns, $parent, $onDelete]) {
            $foreignKeys = collect(Schema::getForeignKeys($table))
                ->filter(fn (array $foreignKey): bool => $foreignKey['columns'] === $columns);
            if ($foreignKeys->count() > 1) {
                throw new RuntimeException(
                    "Terdapat FK duplikat pada $table.".implode(',', $columns).'.'
                );
            }

            $foreignKey = $foreignKeys->first();
            if (! $foreignKey) {
                continue;
            }

            $legacyPivotCascade = $this->isLegacyAgendaCascade(
                $table,
                $columns,
                $foreignKey,
                $parent,
                $onDelete
            );
            if (
                strtolower((string) $foreignKey['foreign_table']) !== $parent
                || $foreignKey['foreign_columns'] !== ['id']
                || (
                    ! in_array(strtolower((string) $foreignKey['on_delete']), [$onDelete, 'no action'], true)
                    && ! $legacyPivotCascade
                )
                || ! in_array(
                    strtolower((string) $foreignKey['on_update']),
                    ['restrict', 'no action'],
                    true
                )
            ) {
                throw new RuntimeException(
                    "Foreign key pada $table.".implode(',', $columns)
                    .' bertentangan dengan DDL target; rekonsiliasi manual diperlukan.'
                );
            }
        }
    }

    private function assertForeignKeyNameCompatibility(bool $includePivot = true): void
    {
        $expected = [
            [self::AGENDA, ['guru_id'], 'fk_agenda_kunjungan_guru'],
            [self::AGENDA, ['instansi_id'], 'fk_agenda_kunjungan_instansi'],
        ];
        if (Schema::hasColumn(self::AGENDA, 'dibatalkan_oleh_user_id')) {
            $expected[] = [
                self::AGENDA,
                ['dibatalkan_oleh_user_id'],
                'fk_agenda_kunjungan_canceled_by',
            ];
        }
        if ($includePivot) {
            $expected[] = [self::PIVOT, ['agenda_kunjungan_id'], 'fk_agenda_siswa_agenda'];
            $expected[] = [self::PIVOT, ['siswa_id'], 'fk_agenda_siswa_siswa'];
        }

        foreach ($expected as [$table, $columns, $name]) {
            $namedForeignKey = collect(Schema::getForeignKeys($table))
                ->first(fn (array $foreignKey): bool => $foreignKey['name'] === $name);

            if ($namedForeignKey && $namedForeignKey['columns'] !== $columns) {
                throw new RuntimeException("$table foreign key $name sudah dipakai pada kolom lain.");
            }
        }
    }

    private function assertNoLegacyCanceledRows(): void
    {
        $cancellationColumns = [
            'alasan_pembatalan',
            'dibatalkan_at',
            'dibatalkan_oleh_user_id',
        ];
        $hasAllCancellationColumns = collect($cancellationColumns)
            ->every(fn (string $column): bool => Schema::hasColumn(self::AGENDA, $column));

        if (
            ! $hasAllCancellationColumns
            && DB::table(self::AGENDA)->where('status', 'dibatalkan')->exists()
        ) {
            throw new RuntimeException(
                'Agenda lama berstatus dibatalkan ditemukan tanpa audit pembatalan; '
                .'rekonsiliasi alasan, waktu, dan pelaku secara manual terlebih dahulu.'
            );
        }

        if ($hasAllCancellationColumns && DB::table(self::AGENDA)
            ->where('status', 'dibatalkan')
            ->where(fn ($query) => $query
                ->whereNull('alasan_pembatalan')
                ->orWhereNull('dibatalkan_at')
                ->orWhereNull('dibatalkan_oleh_user_id'))
            ->exists()) {
            throw new RuntimeException(
                'Agenda dibatalkan tanpa metadata audit lengkap; rekonsiliasi manual diperlukan.'
            );
        }
    }

    private function assertNoOrphanReferences(bool $includePivot = true): void
    {
        $checks = [
            ['agenda_kunjungan as a', 'guru as p', 'a.guru_id', 'p.id'],
            ['agenda_kunjungan as a', 'instansi as p', 'a.instansi_id', 'p.id'],
        ];
        if ($includePivot) {
            $checks[] = ['agenda_kunjungan_siswa as a', 'agenda_kunjungan as p', 'a.agenda_kunjungan_id', 'p.id'];
            $checks[] = ['agenda_kunjungan_siswa as a', 'siswa as p', 'a.siswa_id', 'p.id'];
        }

        foreach ($checks as [$source, $parent, $sourceId, $parentId]) {
            if (
                DB::table($source)
                    ->leftJoin($parent, $parentId, '=', $sourceId)
                    ->whereNull($parentId)
                    ->exists()
            ) {
                throw new RuntimeException(
                    "Referensi agenda yatim ditemukan pada $sourceId; remapping dan rekonsiliasi manual diperlukan."
                );
            }
        }

        if (
            Schema::hasColumn(self::AGENDA, 'dibatalkan_oleh_user_id')
            && DB::table(self::AGENDA.' as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.dibatalkan_oleh_user_id')
                ->whereNotNull('a.dibatalkan_oleh_user_id')
                ->whereNull('u.id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Identitas pembatal agenda yatim; rekonsiliasi manual diperlukan.'
            );
        }
    }

    private function assertExistingForeignKeys(): void
    {
        $this->assertForeignKeyDefinitions();
    }

    private function addAgendaMetadataColumns(): void
    {
        $columns = [
            'alasan_pembatalan' => 'text',
            'dibatalkan_at' => 'timestamp',
            'dibatalkan_oleh_user_id' => 'bigint unsigned',
            'jenis_pencatatan' => 'varchar(20)',
        ];

        foreach ($columns as $name => $type) {
            if (Schema::hasColumn(self::AGENDA, $name)) {
                $column = collect(Schema::getColumns(self::AGENDA))->firstWhere('name', $name);
                if (strtolower((string) ($column['type'] ?? '')) !== $type
                    || ! ($column['nullable'] ?? false)) {
                    throw new RuntimeException(
                        "agenda_kunjungan.$name sudah ada dengan definisi yang tidak kompatibel."
                    );
                }
            }
        }

        Schema::table(self::AGENDA, function (Blueprint $table): void {
            if (! Schema::hasColumn(self::AGENDA, 'alasan_pembatalan')) {
                $table->text('alasan_pembatalan')->nullable();
            }
            if (! Schema::hasColumn(self::AGENDA, 'dibatalkan_at')) {
                $table->timestamp('dibatalkan_at')->nullable();
            }
            if (! Schema::hasColumn(self::AGENDA, 'dibatalkan_oleh_user_id')) {
                $table->unsignedBigInteger('dibatalkan_oleh_user_id')->nullable();
            }
            if (! Schema::hasColumn(self::AGENDA, 'jenis_pencatatan')) {
                $table->string('jenis_pencatatan', 20)->nullable();
            }
        });
    }

    private function addMissingForeignKeys(): void
    {
        $expected = [
            [self::AGENDA, ['guru_id'], 'guru', 'fk_agenda_kunjungan_guru', 'restrict'],
            [self::AGENDA, ['instansi_id'], 'instansi', 'fk_agenda_kunjungan_instansi', 'restrict'],
            [self::PIVOT, ['agenda_kunjungan_id'], self::AGENDA, 'fk_agenda_siswa_agenda', 'restrict'],
            [self::PIVOT, ['siswa_id'], 'siswa', 'fk_agenda_siswa_siswa', 'restrict'],
            [self::AGENDA, ['dibatalkan_oleh_user_id'], 'users', 'fk_agenda_kunjungan_canceled_by', 'restrict'],
        ];

        foreach ($expected as [$table, $columns, $parent, $name, $onDelete]) {
            $existing = $this->findForeignKey($table, $columns);
            if ($existing) {
                if ($this->isLegacyAgendaCascade($table, $columns, $existing, $parent, $onDelete)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
                        $blueprint->dropForeign($existing['name']);
                    });
                } else {
                    continue;
                }
            }

            if ($this->findForeignKey($table, $columns)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use (
                $columns,
                $parent,
                $name,
                $onDelete
            ): void {
                $blueprint->foreign($columns, $name)
                    ->references(['id'])
                    ->on($parent)
                    ->onDelete($onDelete);
            });
        }
    }

    private function addMissingIndexes(): void
    {
        $expected = [
            [self::AGENDA, ['guru_id'], 'idx_agenda_kunjungan_guru'],
            [self::AGENDA, ['instansi_id'], 'idx_agenda_kunjungan_instansi'],
            [self::AGENDA, ['tanggal'], 'idx_agenda_kunjungan_tanggal'],
            [self::PIVOT, ['siswa_id'], 'idx_agenda_kunjungan_siswa_siswa'],
        ];

        foreach ($expected as [$table, $columns, $name]) {
            $hasIndex = collect(Schema::getIndexes($table))
                ->contains(fn (array $index): bool => $index['columns'] === $columns);
            if ($hasIndex) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        }
    }

    private function findForeignKey(string $table, array $columns): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['columns'] === $columns) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function isLegacyAgendaCascade(
        string $table,
        array $columns,
        array $foreignKey,
        string $parent,
        string $onDelete
    ): bool {
        return $table === self::PIVOT
            && $columns === ['agenda_kunjungan_id']
            && $parent === self::AGENDA
            && $onDelete === 'restrict'
            && strtolower((string) $foreignKey['foreign_table']) === self::AGENDA
            && strtolower((string) $foreignKey['on_delete']) === 'cascade';
    }
};
