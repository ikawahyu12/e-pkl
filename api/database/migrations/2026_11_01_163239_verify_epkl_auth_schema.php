<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $requiredColumns = [
            'users' => [
                'id' => ['bigint unsigned', false, null, true],
                'role_id' => ['bigint unsigned', false, null, false],
                'username' => ['varchar(60)', true, null, false],
                'email' => ['varchar(150)', true, null, false],
                'password_hash' => ['varchar(255)', true, null, false],
                'nama_tampilan' => ['varchar(120)', false, null, false],
                'foto_profil' => ['varchar(255)', true, null, false],
                'status' => ["enum('aktif','nonaktif','ditangguhkan')", false, 'aktif', false],
                'wajib_ganti_password' => ['tinyint(1)', false, '0', false],
                'email_terverifikasi_at' => ['datetime', true, null, false],
                'terakhir_login_at' => ['datetime', true, null, false],
                'terakhir_login_ip' => ['varchar(45)', true, null, false],
                'perangkat_terakhir' => ['varchar(150)', true, null, false],
                'created_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
                'updated_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
            ],
            'roles' => [
                'id' => ['bigint unsigned', false, null, true],
                'nama' => ['varchar(30)', false, null, false],
                'deskripsi' => ['varchar(150)', true, null, false],
                'created_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
            ],
            'siswa' => [
                'id' => ['bigint unsigned', false, null, true],
                'user_id' => ['bigint unsigned', true, null, false],
                'nisn' => ['varchar(20)', false, null, false],
                'nis' => ['varchar(30)', true, null, false],
                'nama_lengkap' => ['varchar(120)', false, null, false],
                'kelas_id' => ['bigint unsigned', true, null, false],
                'jenis_kelamin' => ["enum('L','P')", true, null, false],
                'no_hp' => ['varchar(25)', true, null, false],
                'email_sekolah' => ['varchar(150)', true, null, false],
                'alamat' => ['text', true, null, false],
                'nama_orang_tua' => ['varchar(120)', true, null, false],
                'no_hp_orang_tua' => ['varchar(25)', true, null, false],
                'status_siswa' => ["enum('aktif','lulus','keluar')", false, 'aktif', false],
                'created_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
                'updated_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
            ],
            'guru' => [
                'id' => ['bigint unsigned', false, null, true],
                'user_id' => ['bigint unsigned', true, null, false],
                'nip' => ['varchar(30)', true, null, false],
                'nama_lengkap' => ['varchar(120)', false, null, false],
                'jenis_kelamin' => ["enum('L','P')", true, null, false],
                'no_hp' => ['varchar(25)', true, null, false],
                'alamat' => ['text', true, null, false],
                'mata_pelajaran' => ['varchar(100)', true, null, false],
                'jurusan_id' => ['bigint unsigned', true, null, false],
                'status_bimbingan' => ["enum('aktif','nonaktif')", false, 'aktif', false],
                'created_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
                'updated_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
            ],
            'pending_account_verifications' => [
                'id' => ['bigint unsigned', false, null, true],
                'user_id' => ['bigint unsigned', false, null, false],
                'role_id' => ['bigint unsigned', false, null, false],
                'siswa_id' => ['bigint unsigned', true, null, false],
                'guru_id' => ['bigint unsigned', true, null, false],
                'token_hash' => ['char(64)', false, null, false],
                'expires_at' => ['datetime', false, null, false],
                'created_at' => ['timestamp', true, 'CURRENT_TIMESTAMP', false],
            ],
            'personal_access_tokens' => [
                'id' => ['bigint unsigned', false, null, true],
                'tokenable_type' => ['varchar(255)', false, null, false],
                'tokenable_id' => ['bigint unsigned', false, null, false],
                'name' => ['text', false, null, false],
                'token' => ['varchar(64)', false, null, false],
                'abilities' => ['text', true, null, false],
                'last_used_at' => ['timestamp', true, null, false],
                'expires_at' => ['timestamp', true, null, false],
                'created_at' => ['timestamp', true, null, false],
                'updated_at' => ['timestamp', true, null, false],
            ],
        ];

        $requiredIndexes = [
            'users' => [
                [['id'], true, true],
                [['username'], true, false],
                [['email'], true, false],
                [['role_id', 'status'], false, false],
            ],
            'roles' => [
                [['id'], true, true],
                [['nama'], true, false],
            ],
            'siswa' => [
                [['id'], true, true],
                [['user_id'], true, false],
                [['nisn'], true, false],
                [['nis'], true, false],
                [['kelas_id'], false, false],
            ],
            'guru' => [
                [['id'], true, true],
                [['user_id'], true, false],
                [['nip'], true, false],
                [['jurusan_id'], false, false],
            ],
            'pending_account_verifications' => [
                [['id'], true, true],
                [['user_id'], true, false],
                [['role_id'], false, false],
                [['siswa_id'], true, false],
                [['guru_id'], true, false],
                [['token_hash'], true, false],
            ],
            'personal_access_tokens' => [
                [['id'], true, true],
                [['token'], true, false],
                [['tokenable_type', 'tokenable_id'], false, false],
                [['expires_at'], false, false],
            ],
        ];

        $requiredForeignKeys = [
            'users' => [
                [['role_id'], 'roles', ['id'], 'no action', 'no action'],
            ],
            'siswa' => [
                [['user_id'], 'users', ['id'], 'cascade', 'restrict'],
                [['kelas_id'], 'kelas', ['id'], 'cascade', 'set null'],
            ],
            'guru' => [
                [['user_id'], 'users', ['id'], 'cascade', 'restrict'],
                [['jurusan_id'], 'jurusan', ['id'], 'cascade', 'set null'],
            ],
            'pending_account_verifications' => [
                [['user_id'], 'users', ['id'], 'no action', 'cascade'],
                [['role_id'], 'roles', ['id'], 'no action', 'restrict'],
                [['siswa_id'], 'siswa', ['id'], 'no action', 'cascade'],
                [['guru_id'], 'guru', ['id'], 'no action', 'cascade'],
            ],
        ];

        $errors = [];
        $databaseName = Schema::getConnection()->getDatabaseName();
        $normalizeType = static function (string $type): string {
            $type = trim($type);

            if (preg_match('/^(enum|set)\((.*)\)$/i', $type, $matches) === 1) {
                return strtolower($matches[1]).'('.preg_replace('/\s*,\s*/', ',', trim($matches[2])).')';
            }

            return strtolower(preg_replace('/\s+/', '', $type));
        };
        $normalizeDefault = static function (mixed $default): ?string {
            if ($default === null) {
                return null;
            }

            $default = trim((string) $default);
            if (preg_match('/^(current_timestamp|now)\s*(?:\(\s*\d*\s*\))?$/i', $default) === 1) {
                return 'current_timestamp';
            }

            return strtolower($default);
        };
        $normalizeForeignKeyRule = static function (?string $rule): string {
            $rule = strtolower(trim((string) $rule));

            return $rule === 'no action' ? 'restrict' : $rule;
        };

        foreach ($requiredColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $errors[] = "Tabel epkl.$table belum tersedia.";
                continue;
            }

            $actualColumns = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($columns as $name => [$type, $nullable, $default, $autoIncrement]) {
                $column = $actualColumns->get($name);
                $actualDefault = $column['default'] ?? null;

                if (
                    ! $column
                    || $normalizeType($column['type']) !== $normalizeType($type)
                    || $column['nullable'] !== $nullable
                    || $normalizeDefault($actualDefault) !== $normalizeDefault($default)
                    || $column['auto_increment'] !== $autoIncrement
                ) {
                    $errors[] = "epkl.$table.$name tidak cocok; diharapkan $type"
                        .($nullable ? ' nullable' : ' NOT NULL')
                        .($default === null ? ' tanpa default' : " default $default")
                        .($autoIncrement ? ' AUTO_INCREMENT.' : '.');
                }
            }

            $actualIndexes = Schema::getIndexes($table);
            foreach ($requiredIndexes[$table] as [$indexColumns, $unique, $primary]) {
                $found = false;
                foreach ($actualIndexes as $index) {
                    if (
                        $index['columns'] === $indexColumns
                        && $index['unique'] === $unique
                        && $index['primary'] === $primary
                    ) {
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    $errors[] = 'epkl.'.$table.' memerlukan '
                        .($primary ? 'primary key' : ($unique ? 'unique index' : 'index'))
                        .' pada '.implode(', ', $indexColumns).'.';
                }
            }

            foreach ($requiredForeignKeys[$table] ?? [] as [
                $columns,
                $foreignTable,
                $foreignColumns,
                $onUpdate,
                $onDelete,
            ]) {
                $found = false;
                foreach (Schema::getForeignKeys($table) as $foreignKey) {
                    if (
                        $foreignKey['columns'] === $columns
                        && strcasecmp((string) $foreignKey['foreign_schema'], $databaseName) === 0
                        && $foreignKey['foreign_table'] === $foreignTable
                        && $foreignKey['foreign_columns'] === $foreignColumns
                        && $normalizeForeignKeyRule($foreignKey['on_update']) === $normalizeForeignKeyRule($onUpdate)
                        && $normalizeForeignKeyRule($foreignKey['on_delete']) === $normalizeForeignKeyRule($onDelete)
                    ) {
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    $errors[] = 'Foreign key epkl.'.$table.'.'.implode(', ', $columns)
                        .' harus merujuk ke epkl.'.$foreignTable.'.'.implode(', ', $foreignColumns)
                        ." dengan ON UPDATE $onUpdate dan ON DELETE $onDelete.";
                }
            }
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $errors[] = 'Validasi constraint CHECK membutuhkan koneksi MySQL.';
        } elseif (Schema::hasTable('pending_account_verifications')) {
            $checks = DB::select(
                "SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE, tc.ENFORCED
                FROM information_schema.TABLE_CONSTRAINTS AS tc
                INNER JOIN information_schema.CHECK_CONSTRAINTS AS cc
                    ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                    AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                WHERE tc.TABLE_SCHEMA = DATABASE()
                    AND tc.TABLE_NAME = ?
                    AND tc.CONSTRAINT_TYPE = 'CHECK'",
                ['pending_account_verifications'],
            );
            $checkFound = false;

            foreach ($checks as $check) {
                if (
                    $check->CONSTRAINT_NAME === 'chk_pending_role_master'
                    && strtoupper((string) $check->ENFORCED) === 'YES'
                    && $this->isExactlyOneProfileCheck((string) $check->CHECK_CLAUSE)
                ) {
                    $checkFound = true;
                    break;
                }
            }

            if (! $checkFound) {
                $errors[] = 'epkl.pending_account_verifications memerlukan CHECK '
                    .'chk_pending_role_master yang mewajibkan tepat satu profil siswa/guru terisi '
                    .'dan berstatus enforced.';
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException(
                "Skema E-PKL tidak sesuai dengan prasyarat aplikasi:\n- ".implode("\n- ", $errors)
            );
        }
    }

    private function isExactlyOneProfileCheck(string $expression): bool
    {
        $expression = strtolower(str_replace('`', '', trim($expression)));
        $unparsedCharacters = preg_replace('/[a-z_][a-z0-9_]*|[()]/i', '', $expression);

        if ($unparsedCharacters === null || preg_match('/\S/', $unparsedCharacters) === 1) {
            return false;
        }

        preg_match_all('/[a-z_][a-z0-9_]*|[()]/i', $expression, $matches);
        $tokens = $matches[0];
        $position = 0;

        if ($tokens === []) {
            return false;
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
        if ($tree === null || $position !== count($tokens)) {
            return false;
        }

        $evaluate = static function (array $node, bool $siswaPresent, bool $guruPresent) use (&$evaluate): bool {
            return match ($node[0]) {
                'null' => $node[1] === 'siswa_id' ? ! $siswaPresent : ! $guruPresent,
                'not_null' => $node[1] === 'siswa_id' ? $siswaPresent : $guruPresent,
                'and' => $evaluate($node[1], $siswaPresent, $guruPresent)
                    && $evaluate($node[2], $siswaPresent, $guruPresent),
                'or' => $evaluate($node[1], $siswaPresent, $guruPresent)
                    || $evaluate($node[2], $siswaPresent, $guruPresent),
                default => false,
            };
        };

        foreach ([false, true] as $siswaPresent) {
            foreach ([false, true] as $guruPresent) {
                $exactlyOneProfilePresent = $siswaPresent !== $guruPresent;

                if ($evaluate($tree, $siswaPresent, $guruPresent) !== $exactlyOneProfilePresent) {
                    return false;
                }
            }
        }

        return true;
    }

    public function down(): void
    {
        // The epkl business schema is managed outside this API repository.
    }
};
