<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'pending_account_verifications';

    private const CHECK_NAME = 'chk_pending_role_master';

    public function up(): void
    {
        $this->assertMySqlSupportsEnforcedChecks();
        $this->assertParentTablesAreCompatible();

        if (Schema::hasTable(self::TABLE)) {
            $errors = $this->existingTableSchemaErrors(allowLegacyDefinitions: true);
            $databaseName = Schema::getConnection()->getDatabaseName();
            if ($errors !== []) {
                throw new \RuntimeException(
                    'Tabel '.$databaseName.'.'.self::TABLE.' tidak dapat direkonsiliasi dengan aman: '
                    .implode("\n- ", $errors)
                );
            }

            $this->reconcileExistingTable();
            $errors = $this->existingTableSchemaErrors();
            if ($errors !== []) {
                throw new \RuntimeException(
                    'Rekonsiliasi '.$databaseName.'.'.self::TABLE.' belum menghasilkan struktur target: '
                    .implode("\n- ", $errors)
                );
            }

            return;
        }

        DB::statement(<<<'SQL'
            CREATE TABLE `pending_account_verifications` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `role_id` BIGINT UNSIGNED NOT NULL,
                `siswa_id` BIGINT UNSIGNED NULL,
                `guru_id` BIGINT UNSIGNED NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_pending_account_verifications_user` (`user_id`),
                KEY `idx_pending_account_verifications_role` (`role_id`),
                UNIQUE KEY `uq_pending_account_verifications_siswa` (`siswa_id`),
                UNIQUE KEY `uq_pending_account_verifications_guru` (`guru_id`),
                UNIQUE KEY `uq_pending_account_verifications_token` (`token_hash`),
                CONSTRAINT `fk_pending_account_verifications_user`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                    ON DELETE CASCADE ON UPDATE NO ACTION,
                CONSTRAINT `fk_pending_account_verifications_role`
                    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
                    ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT `fk_pending_account_verifications_siswa`
                    FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`)
                    ON DELETE CASCADE ON UPDATE NO ACTION,
                CONSTRAINT `fk_pending_account_verifications_guru`
                    FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`)
                    ON DELETE CASCADE ON UPDATE NO ACTION,
                CONSTRAINT `chk_pending_role_master` CHECK (
                    (`siswa_id` IS NOT NULL AND `guru_id` IS NULL)
                    OR (`siswa_id` IS NULL AND `guru_id` IS NOT NULL)
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $databaseName = Schema::getConnection()->getDatabaseName();

        throw new \RuntimeException(
            'Rollback otomatis '.$databaseName.'.'.self::TABLE.' ditolak karena migration tidak dapat '
            .'membuktikan provenance/kepemilikan tabel dari metadata skema. Pastikan tabel '
            .'masih dikelola migration ini, lakukan backup, hentikan penulisan aplikasi, dan '
            .'hapus tabel secara terkontrol di luar rollback otomatis.'
        );
    }

    private function assertMySqlSupportsEnforcedChecks(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Migration verifikasi akun memerlukan MySQL dengan CHECK constraint enforced.'
            );
        }

        $version = DB::selectOne('SELECT VERSION() AS version')?->version;
        if (! is_string($version) || version_compare($version, '8.0.16', '<')) {
            throw new \RuntimeException(
                'Migration verifikasi akun memerlukan MySQL 8.0.16 atau lebih baru '
                .'agar CHECK constraint ditegakkan.'
            );
        }
    }

    private function assertParentTablesAreCompatible(): void
    {
        $errors = [];
        $databaseName = Schema::getConnection()->getDatabaseName();

        foreach (['users', 'roles', 'siswa', 'guru'] as $table) {
            if (! Schema::hasTable($table)) {
                $errors[] = "Tabel induk epkl.$table belum tersedia.";

                continue;
            }

            $columns = collect(Schema::getColumns($table))->keyBy('name');
            $id = $columns->get('id');
            if (
                ! $id
                || $this->normalizeType((string) $id['type']) !== 'bigint unsigned'
                || $id['nullable'] !== false
            ) {
                $errors[] = "epkl.$table.id harus BIGINT UNSIGNED NOT NULL.";
            }

            $hasPrimaryKey = false;
            foreach (Schema::getIndexes($table) as $index) {
                if ($index['columns'] === ['id'] && $index['primary'] === true) {
                    $hasPrimaryKey = true;

                    break;
                }
            }
            if (! $hasPrimaryKey) {
                $errors[] = "epkl.$table.id harus menjadi primary key.";
            }

            $metadata = $this->tableMetadata($table, $databaseName);
            if (strtolower((string) ($metadata->ENGINE ?? '')) !== 'innodb') {
                $errors[] = "epkl.$table harus menggunakan engine InnoDB untuk foreign key.";
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException(
                "Prasyarat tabel verifikasi akun tidak terpenuhi:\n- ".implode("\n- ", $errors)
            );
        }
    }

    private function existingTableSchemaErrors(bool $allowLegacyDefinitions = false): array
    {
        $databaseName = Schema::getConnection()->getDatabaseName();
        $metadata = $this->tableMetadata(self::TABLE, $databaseName);
        $columns = DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, '
                .'GENERATION_EXPRESSION, COLUMN_COMMENT, CHARACTER_SET_NAME, COLLATION_NAME, '
                .'SRS_ID '
                .'FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'ORDER BY ORDINAL_POSITION',
            [self::TABLE],
        );
        $indexes = DB::select(
            'SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, INDEX_TYPE, '
                .'IS_VISIBLE, EXPRESSION, COLLATION, INDEX_COMMENT '
                .'FROM information_schema.STATISTICS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [self::TABLE],
        );
        $checks = DB::select(
            "SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE, tc.ENFORCED
            FROM information_schema.TABLE_CONSTRAINTS AS tc
            INNER JOIN information_schema.CHECK_CONSTRAINTS AS cc
                ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
                AND tc.TABLE_NAME = ?
                AND tc.CONSTRAINT_TYPE = 'CHECK'",
            [self::TABLE],
        );

        return $this->schemaErrors(
            Schema::getColumns(self::TABLE),
            $columns,
            $indexes,
            Schema::getForeignKeys(self::TABLE),
            $checks,
            (string) ($metadata->ENGINE ?? ''),
            (string) ($metadata->TABLE_COLLATION ?? ''),
            $databaseName,
            $allowLegacyDefinitions,
        );
    }

    private function reconcileExistingTable(): void
    {
        $invalidRows = (int) (DB::selectOne(
            'SELECT COUNT(*) AS invalid_rows FROM `'.self::TABLE.'` '
                .'WHERE (`siswa_id` IS NULL) = (`guru_id` IS NULL)',
        )->invalid_rows ?? 0);
        if ($invalidRows !== 0) {
            throw new \RuntimeException(
                'Rekonsiliasi ditolak: terdapat baris yang tidak memenuhi aturan XOR profil.'
            );
        }

        $indexes = DB::select(
            'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE],
        );
        $foreignKeys = DB::select(
            'SELECT DISTINCT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
                .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'AND REFERENCED_TABLE_NAME IS NOT NULL',
            [self::TABLE],
        );
        $checks = DB::select(
            "SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE
            FROM information_schema.TABLE_CONSTRAINTS AS tc
            INNER JOIN information_schema.CHECK_CONSTRAINTS AS cc
                ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
                AND tc.TABLE_NAME = ?
                AND tc.CONSTRAINT_TYPE = 'CHECK'",
            [self::TABLE],
        );

        foreach ($this->reconciliationStatements($indexes, $foreignKeys, $checks) as $statement) {
            DB::statement($statement);
        }
    }

    private function reconciliationStatements(array $indexes, array $foreignKeys, array $checks): array
    {
        $statements = [];
        $indexAliases = [
            'uq_pending_user' => 'uq_pending_account_verifications_user',
            'fk_pending_role' => 'idx_pending_account_verifications_role',
            'uq_pending_siswa' => 'uq_pending_account_verifications_siswa',
            'uq_pending_guru' => 'uq_pending_account_verifications_guru',
            'uq_pending_token' => 'uq_pending_account_verifications_token',
        ];
        $renameIndexes = [];
        foreach ($indexes as $index) {
            $name = (string) ($index->INDEX_NAME ?? '');
            if (isset($indexAliases[$name])) {
                $renameIndexes[] = 'RENAME INDEX `'.$name.'` TO `'.$indexAliases[$name].'`';
            }
        }
        if ($renameIndexes !== []) {
            $statements[] = 'ALTER TABLE `'.self::TABLE.'` '.implode(', ', $renameIndexes);
        }

        $foreignKeyAliases = [
            'fk_pending_user' => [
                'fk_pending_account_verifications_user',
                'user_id',
                'users',
                'CASCADE',
            ],
            'fk_pending_role' => [
                'fk_pending_account_verifications_role',
                'role_id',
                'roles',
                'RESTRICT',
            ],
            'fk_pending_siswa' => [
                'fk_pending_account_verifications_siswa',
                'siswa_id',
                'siswa',
                'CASCADE',
            ],
            'fk_pending_guru' => [
                'fk_pending_account_verifications_guru',
                'guru_id',
                'guru',
                'CASCADE',
            ],
        ];
        foreach ($foreignKeys as $foreignKey) {
            $name = (string) ($foreignKey->CONSTRAINT_NAME ?? '');
            if (! isset($foreignKeyAliases[$name])) {
                continue;
            }

            [$targetName, $column, $referencedTable, $deleteRule] = $foreignKeyAliases[$name];
            if ($name === $targetName) {
                continue;
            }

            $statements[] = 'ALTER TABLE `'.self::TABLE.'` DROP FOREIGN KEY `'.$name.'`, '
                .'ADD CONSTRAINT `'.$targetName.'` FOREIGN KEY (`'.$column.'`) '
                .'REFERENCES `'.$referencedTable.'` (`id`) '
                .'ON DELETE '.$deleteRule.' ON UPDATE NO ACTION';
        }

        if (
            count($checks) === 1
            && $this->isLegacyRoleBoundCheck((string) ($checks[0]->CHECK_CLAUSE ?? ''))
        ) {
            $statements[] = 'ALTER TABLE `'.self::TABLE.'` DROP CHECK `'.self::CHECK_NAME.'`, '
                .'ADD CONSTRAINT `'.self::CHECK_NAME.'` CHECK ('
                .'(`siswa_id` IS NOT NULL AND `guru_id` IS NULL) OR '
                .'(`siswa_id` IS NULL AND `guru_id` IS NOT NULL)) ENFORCED';
        }

        return $statements;
    }

    private function tableMetadata(string $table, string $databaseName): ?object
    {
        return DB::selectOne(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES '
                .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$databaseName, $table],
        );
    }

    private function schemaErrors(
        array $columns,
        array $columnMetadata,
        array $indexes,
        array $foreignKeys,
        array $checks,
        string $engine,
        string $collation,
        string $databaseName,
        bool $allowLegacyDefinitions = false,
    ): array {
        $errors = [];
        $expectedColumns = [
            'id' => ['bigint unsigned', false, null, true, 'auto_increment', null, null],
            'user_id' => ['bigint unsigned', false, null, false, '', null, null],
            'role_id' => ['bigint unsigned', false, null, false, '', null, null],
            'siswa_id' => ['bigint unsigned', true, null, false, '', null, null],
            'guru_id' => ['bigint unsigned', true, null, false, '', null, null],
            'token_hash' => ['char(64)', false, null, false, '', 'utf8mb4', 'utf8mb4_unicode_ci'],
            'expires_at' => ['datetime', false, null, false, '', null, null],
            'created_at' => ['timestamp', true, 'current_timestamp', false, 'default_generated', null, null],
        ];
        $actualColumns = collect($columns)->keyBy('name');
        $actualMetadata = collect($columnMetadata)->keyBy('COLUMN_NAME');

        if ($actualColumns->keys()->sort()->values()->all() !== collect(array_keys($expectedColumns))->sort()->values()->all()) {
            $errors[] = 'Daftar kolom tidak sama dengan struktur target.';
        }

        if (
            $actualMetadata->count() !== count($columnMetadata)
            || $actualMetadata->keys()->sort()->values()->all() !== collect(array_keys($expectedColumns))->sort()->values()->all()
        ) {
            $errors[] = 'Metadata information_schema.COLUMNS memiliki kolom duplikat, hilang, atau tambahan.';
        }

        foreach ($expectedColumns as $name => [$type, $nullable, $default, $autoIncrement, $extra, $charset, $expectedCollation]) {
            $column = $actualColumns->get($name);
            $metadata = $actualMetadata->get($name);
            if (! $column || ! $metadata) {
                $errors[] = "Kolom atau metadata information_schema.COLUMNS untuk $name tidak tersedia.";

                continue;
            }

            $requiredMetadataFields = [
                'COLUMN_TYPE',
                'IS_NULLABLE',
                'COLUMN_DEFAULT',
                'EXTRA',
                'GENERATION_EXPRESSION',
                'COLUMN_COMMENT',
                'CHARACTER_SET_NAME',
                'COLLATION_NAME',
                'SRS_ID',
            ];
            $missingMetadataFields = array_filter(
                $requiredMetadataFields,
                static fn (string $field): bool => ! property_exists($metadata, $field),
            );
            if ($missingMetadataFields !== []) {
                $errors[] = "Metadata information_schema.COLUMNS untuk $name tidak lengkap.";

                continue;
            }

            if (
                $this->normalizeType((string) $column['type']) !== $type
                || $column['nullable'] !== $nullable
                || $this->normalizeDefault($column['default'] ?? null) !== $default
                || $column['auto_increment'] !== $autoIncrement
            ) {
                $errors[] = "Kolom $name tidak sesuai tipe, nullability, default, atau AUTO_INCREMENT.";
            }

            if (
                $this->normalizeType((string) $metadata->COLUMN_TYPE) !== $type
                || strtoupper((string) $metadata->IS_NULLABLE) !== ($nullable ? 'YES' : 'NO')
                || $this->normalizeDefault($metadata->COLUMN_DEFAULT) !== $default
                || strtolower(trim((string) $metadata->EXTRA)) !== $extra
                || trim((string) $metadata->GENERATION_EXPRESSION) !== ''
                || $metadata->COLUMN_COMMENT !== ''
                || $this->nullableMetadataString($metadata->CHARACTER_SET_NAME) !== $charset
                || $this->nullableMetadataString($metadata->COLLATION_NAME) !== $expectedCollation
                || $metadata->SRS_ID !== null
            ) {
                $errors[] = "Atribut metadata information_schema.COLUMNS untuk $name tidak sesuai "
                    .'(COLUMN_TYPE, nullability, default, EXTRA, generation, comment, charset/collation, atau SRS_ID).';
            }
        }

        $expectedIndexes = [
            ['PRIMARY', ['id'], true, true],
            ['uq_pending_account_verifications_user', ['user_id'], true, false],
            ['idx_pending_account_verifications_role', ['role_id'], false, false],
            ['uq_pending_account_verifications_siswa', ['siswa_id'], true, false],
            ['uq_pending_account_verifications_guru', ['guru_id'], true, false],
            ['uq_pending_account_verifications_token', ['token_hash'], true, false],
        ];
        if ($allowLegacyDefinitions) {
            $indexes = $this->normalizeLegacyIndexNames($indexes);
            $foreignKeys = $this->normalizeLegacyForeignKeyNames($foreignKeys);
            $checks = $this->normalizeLegacyChecks($checks);
        }
        if (! $this->sameIndexDefinitions($indexes, $expectedIndexes)) {
            $errors[] = 'Primary key, unique index, atau index biasa tidak sesuai struktur target.';
        }

        $expectedForeignKeys = [
            ['fk_pending_account_verifications_user', ['user_id'], 'users', ['id'], 'restrict', 'cascade'],
            ['fk_pending_account_verifications_role', ['role_id'], 'roles', ['id'], 'restrict', 'restrict'],
            ['fk_pending_account_verifications_siswa', ['siswa_id'], 'siswa', ['id'], 'restrict', 'cascade'],
            ['fk_pending_account_verifications_guru', ['guru_id'], 'guru', ['id'], 'restrict', 'cascade'],
        ];
        if (! $this->sameForeignKeyDefinitions($foreignKeys, $expectedForeignKeys, $databaseName)) {
            $errors[] = 'Foreign key, tabel/kolom tujuan, atau aturan ON DELETE/ON UPDATE tidak sesuai.';
        }

        $validChecks = array_values(array_filter(
            $checks,
            fn (object $check): bool => ($check->CONSTRAINT_NAME ?? null) === self::CHECK_NAME
                && strtoupper((string) ($check->ENFORCED ?? '')) === 'YES'
                && $this->isExactlyOneProfileCheck((string) ($check->CHECK_CLAUSE ?? '')),
        ));
        if (count($checks) !== 1 || count($validChecks) !== 1) {
            $errors[] = 'CHECK chk_pending_role_master harus enforced dan menegakkan XOR profil tanpa role_id.';
        }

        if (strtolower($engine) !== 'innodb') {
            $errors[] = 'Engine tabel harus InnoDB.';
        }
        if (strtolower($collation) !== 'utf8mb4_unicode_ci') {
            $errors[] = 'Collation tabel harus utf8mb4_unicode_ci.';
        }

        return $errors;
    }

    private function sameIndexDefinitions(array $actual, array $expected): bool
    {
        $grouped = [];
        foreach ($actual as $index) {
            $name = (string) ($index->INDEX_NAME ?? '');
            if (
                $name === ''
                || ! property_exists($index, 'SEQ_IN_INDEX')
                || ! is_numeric($index->SEQ_IN_INDEX)
                || (float) $index->SEQ_IN_INDEX !== (float) (int) $index->SEQ_IN_INDEX
                || ! property_exists($index, 'NON_UNIQUE')
                || ! is_numeric($index->NON_UNIQUE)
                || (float) $index->NON_UNIQUE !== (float) (int) $index->NON_UNIQUE
                || ! in_array((int) $index->NON_UNIQUE, [0, 1], true)
                || ! property_exists($index, 'COLUMN_NAME')
            ) {
                return false;
            }

            $grouped[$name][] = $index;
        }

        $actualSignature = [];
        foreach ($grouped as $name => $rows) {
            $positions = [];
            foreach ($rows as $row) {
                if (
                    (int) $row->SEQ_IN_INDEX < 1
                    || isset($positions[(int) $row->SEQ_IN_INDEX])
                    || ! $this->hasIndexMetadata($row)
                    || ($row->SUB_PART ?? null) !== null
                    || ($row->EXPRESSION ?? null) !== null
                    || strtoupper((string) ($row->INDEX_TYPE ?? '')) !== 'BTREE'
                    || strtoupper((string) ($row->IS_VISIBLE ?? '')) !== 'YES'
                    || strtoupper((string) ($row->COLLATION ?? '')) !== 'A'
                    || ($row->INDEX_COMMENT ?? null) !== ''
                ) {
                    return false;
                }

                $positions[(int) $row->SEQ_IN_INDEX] = $row->COLUMN_NAME === null
                    ? null
                    : (string) $row->COLUMN_NAME;
            }

            ksort($positions);
            if (array_keys($positions) !== range(1, count($rows)) || in_array(null, $positions, true)) {
                return false;
            }
            $columns = array_values($positions);
            $nonUnique = (int) $rows[0]->NON_UNIQUE;
            foreach ($rows as $row) {
                if ((int) $row->NON_UNIQUE !== $nonUnique) {
                    return false;
                }
            }

            $actualSignature[] = [$name, $columns, $nonUnique === 0, strtoupper($name) === 'PRIMARY'];
        }

        $expectedSignature = array_map(
            static fn (array $index): array => [
                $index[0],
                $index[1],
                (bool) $index[2],
                (bool) $index[3],
            ],
            $expected,
        );
        $sort = static function (array &$signature): void {
            usort($signature, static fn (array $left, array $right): int => strcmp(serialize($left), serialize($right)));
        };
        $sort($actualSignature);
        $sort($expectedSignature);

        return $actualSignature === $expectedSignature;
    }

    private function hasIndexMetadata(object $index): bool
    {
        foreach ([
            'SUB_PART',
            'EXPRESSION',
            'INDEX_TYPE',
            'IS_VISIBLE',
            'COLLATION',
            'INDEX_COMMENT',
        ] as $field) {
            if (! property_exists($index, $field)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeLegacyIndexNames(array $indexes): array
    {
        $aliases = [
            'uq_pending_user' => 'uq_pending_account_verifications_user',
            'fk_pending_role' => 'idx_pending_account_verifications_role',
            'uq_pending_siswa' => 'uq_pending_account_verifications_siswa',
            'uq_pending_guru' => 'uq_pending_account_verifications_guru',
            'uq_pending_token' => 'uq_pending_account_verifications_token',
        ];

        foreach ($indexes as $position => $index) {
            $name = (string) ($index->INDEX_NAME ?? '');
            if (isset($aliases[$name])) {
                $normalized = clone $index;
                $normalized->INDEX_NAME = $aliases[$name];
                $indexes[$position] = $normalized;
            }
        }

        return $indexes;
    }

    private function normalizeLegacyForeignKeyNames(array $foreignKeys): array
    {
        $aliases = [
            'fk_pending_user' => 'fk_pending_account_verifications_user',
            'fk_pending_role' => 'fk_pending_account_verifications_role',
            'fk_pending_siswa' => 'fk_pending_account_verifications_siswa',
            'fk_pending_guru' => 'fk_pending_account_verifications_guru',
        ];

        foreach ($foreignKeys as $position => $foreignKey) {
            $name = $foreignKey['name'] ?? '';
            if (isset($aliases[$name])) {
                $foreignKeys[$position]['name'] = $aliases[$name];
            }
        }

        return $foreignKeys;
    }

    private function normalizeLegacyChecks(array $checks): array
    {
        foreach ($checks as $position => $check) {
            if ($this->isLegacyRoleBoundCheck((string) ($check->CHECK_CLAUSE ?? ''))) {
                $normalized = clone $check;
                $normalized->CHECK_CLAUSE = '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(siswa_id IS NULL AND guru_id IS NOT NULL)';
                $checks[$position] = $normalized;
            }
        }

        return $checks;
    }

    private function isLegacyRoleBoundCheck(string $expression): bool
    {
        $normalized = strtolower((string) preg_replace('/[\s`()]/', '', $expression));
        $legacyExpressions = [
            'role_id=2andguru_idisnotnullandsiswa_idisnullor'
                .'role_id=3andsiswa_idisnotnullandguru_idisnull',
            'role_id=3andsiswa_idisnotnullandguru_idisnullor'
                .'role_id=2andguru_idisnotnullandsiswa_idisnull',
            'role_id=2andsiswa_idisnotnullandguru_idisnullor'
                .'role_id=3andguru_idisnotnullandsiswa_idisnull',
            'role_id=3andguru_idisnotnullandsiswa_idisnullor'
                .'role_id=2andsiswa_idisnotnullandguru_idisnull',
        ];

        return in_array($normalized, $legacyExpressions, true);
    }

    private function nullableMetadataString(mixed $value): ?string
    {
        return $value === null ? null : strtolower(trim((string) $value));
    }

    private function sameForeignKeyDefinitions(
        array $actual,
        array $expected,
        string $databaseName,
    ): bool {
        $normalizeRule = fn (?string $rule): string => $this->normalizeForeignKeyRule($rule);
        $signature = static function (array $foreignKeys, bool $isActual) use ($databaseName, $normalizeRule): array {
            $result = [];
            foreach ($foreignKeys as $foreignKey) {
                if ($isActual && strcasecmp((string) $foreignKey['foreign_schema'], $databaseName) !== 0) {
                    $result[] = ['different_schema'];

                    continue;
                }

                $result[] = [
                    $isActual ? $foreignKey['name'] : $foreignKey[0],
                    $isActual ? $foreignKey['columns'] : $foreignKey[1],
                    $isActual ? $foreignKey['foreign_table'] : $foreignKey[2],
                    $isActual ? $foreignKey['foreign_columns'] : $foreignKey[3],
                    $normalizeRule($isActual ? $foreignKey['on_update'] : $foreignKey[4]),
                    $normalizeRule($isActual ? $foreignKey['on_delete'] : $foreignKey[5]),
                ];
            }
            usort($result, static fn (array $left, array $right): int => strcmp(serialize($left), serialize($right)));

            return $result;
        };

        return $signature($actual, true) === $signature($expected, false);
    }

    private function normalizeForeignKeyRule(?string $rule): string
    {
        $rule = strtolower(trim((string) $rule));

        return $rule === 'no action' ? 'restrict' : $rule;
    }

    private function normalizeType(string $type): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($type)));
    }

    private function normalizeDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = strtolower(trim((string) $default));
        if (preg_match('/^(current_timestamp|now)\s*(?:\(\s*\d*\s*\))?$/', $default) === 1) {
            return 'current_timestamp';
        }

        return trim($default, "'\"");
    }

    private function isExactlyOneProfileCheck(string $expression): bool
    {
        $tree = $this->parseProfileCheck($expression);
        if ($tree === null) {
            return false;
        }

        foreach ([false, true] as $siswaPresent) {
            foreach ([false, true] as $guruPresent) {
                if (
                    $this->evaluateProfileCheck($tree, $siswaPresent, $guruPresent)
                    !== ($siswaPresent !== $guruPresent)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    private function parseProfileCheck(string $expression): ?array
    {
        $expression = strtolower(str_replace('`', '', trim($expression)));
        $unparsedCharacters = preg_replace('/[a-z_][a-z0-9_]*|[()]/i', '', $expression);
        if ($unparsedCharacters === null || preg_match('/\S/', $unparsedCharacters) === 1) {
            return null;
        }

        preg_match_all('/[a-z_][a-z0-9_]*|[()]/i', $expression, $matches);
        $tokens = $matches[0];
        $position = 0;
        if ($tokens === []) {
            return null;
        }

        $parseOr = null;
        $parseAnd = null;
        $parsePrimary = null;

        $parsePrimary = static function () use (&$tokens, &$position, &$parseOr): ?array {
            if (($tokens[$position] ?? null) === '(') {
                $position++;
                $node = $parseOr();
                if ($node === null || ($tokens[$position] ?? null) !== ')') {
                    return null;
                }
                $position++;

                return $node;
            }

            $column = $tokens[$position] ?? null;
            if (! in_array($column, ['siswa_id', 'guru_id'], true)) {
                return null;
            }
            $position++;

            if (strtolower($tokens[$position] ?? '') !== 'is') {
                return null;
            }
            $position++;

            $not = strtolower($tokens[$position] ?? '') === 'not';
            if ($not) {
                $position++;
            }
            if (strtolower($tokens[$position] ?? '') !== 'null') {
                return null;
            }
            $position++;

            return [$not ? 'not_null' : 'null', $column];
        };

        $parseAnd = static function () use (&$tokens, &$position, &$parsePrimary): ?array {
            $node = $parsePrimary();
            if ($node === null) {
                return null;
            }
            while (strtolower($tokens[$position] ?? '') === 'and') {
                $position++;
                $right = $parsePrimary();
                if ($right === null) {
                    return null;
                }
                $node = ['and', $node, $right];
            }

            return $node;
        };

        $parseOr = static function () use (&$tokens, &$position, &$parseAnd): ?array {
            $node = $parseAnd();
            if ($node === null) {
                return null;
            }
            while (strtolower($tokens[$position] ?? '') === 'or') {
                $position++;
                $right = $parseAnd();
                if ($right === null) {
                    return null;
                }
                $node = ['or', $node, $right];
            }

            return $node;
        };

        $tree = $parseOr();

        return $tree !== null && $position === count($tokens) ? $tree : null;
    }

    private function evaluateProfileCheck(array $node, bool $siswaPresent, bool $guruPresent): bool
    {
        return match ($node[0]) {
            'null' => $node[1] === 'siswa_id' ? ! $siswaPresent : ! $guruPresent,
            'not_null' => $node[1] === 'siswa_id' ? $siswaPresent : $guruPresent,
            'and' => $this->evaluateProfileCheck($node[1], $siswaPresent, $guruPresent)
                && $this->evaluateProfileCheck($node[2], $siswaPresent, $guruPresent),
            'or' => $this->evaluateProfileCheck($node[1], $siswaPresent, $guruPresent)
                || $this->evaluateProfileCheck($node[2], $siswaPresent, $guruPresent),
            default => false,
        };
    }
};
