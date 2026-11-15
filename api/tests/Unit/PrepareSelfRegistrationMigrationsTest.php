<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use RuntimeException;

class PrepareSelfRegistrationMigrationsTest extends TestCase
{
    private $database;

    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->database = Mockery::mock();
        $this->schema = Mockery::mock();
        DB::swap($this->database);
        Schema::swap($this->schema);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    private function migration(string $filename): object
    {
        return require __DIR__.'/../../database/migrations/'.$filename;
    }

    private function invoke(object $migration, string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod($migration, $method))->invoke($migration, ...$arguments);
    }

    public function test_status_migration_accepts_only_the_expected_enum_and_default(): void
    {
        $migration = $this->migration('2026_11_13_000001_add_pending_status_to_users_table.php');

        self::assertTrue($this->invoke($migration, 'statusColumnMatches', [
            'type' => "enum('pending','aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'extra' => '',
            'generation_expression' => '',
            'srs_id' => null,
        ], "enum('pending','aktif','nonaktif','ditangguhkan')"));

        self::assertTrue($this->invoke($migration, 'statusColumnMatches', [
            'type' => "enum('aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'keep this',
            'extra' => '',
            'generation_expression' => '',
            'srs_id' => null,
        ], "enum('aktif','nonaktif','ditangguhkan')"));

        self::assertFalse($this->invoke($migration, 'statusColumnMatches', [
            'type' => "enum('aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'extra' => 'INVISIBLE',
            'generation_expression' => '',
            'srs_id' => null,
        ], "enum('aktif','nonaktif','ditangguhkan')"));
    }

    public function test_status_up_executes_alter_preserving_existing_column_attributes(): void
    {
        $migration = $this->migration('2026_11_13_000001_add_pending_status_to_users_table.php');
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $this->schema->shouldReceive('getConnection')->once()->andReturn($connection);
        $this->schema->shouldReceive('hasTable')->once()->with('users')->andReturn(true);
        $this->database->shouldReceive('selectOne')->once()->withArgs(
            static function (string $sql): bool {
                self::assertStringContainsString('GENERATION_EXPRESSION', $sql);
                self::assertStringContainsString('SRS_ID', $sql);
                self::assertStringNotContainsString('COLUMN_FORMAT', $sql);

                return true;
            },
        )->andReturn((object) [
            'type' => "enum('aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'comment' => "account's state",
            'extra' => '',
            'generation_expression' => '',
            'srs_id' => null,
        ]);
        $this->database->shouldReceive('statement')->once()->withArgs(
            static function (string $sql): bool {
                self::assertStringContainsString('ALTER TABLE `users` MODIFY `status`', $sql);
                self::assertStringContainsString('CHARACTER SET `utf8mb4`', $sql);
                self::assertStringContainsString('COLLATE `utf8mb4_bin`', $sql);
                self::assertStringContainsString("COMMENT 'account''s state'", $sql);
                self::assertStringContainsString("DEFAULT 'aktif'", $sql);

                return true;
            },
        );

        $migration->up();
    }

    public function test_status_up_rejects_unpreservable_attributes_before_alter(): void
    {
        $migration = $this->migration('2026_11_13_000001_add_pending_status_to_users_table.php');
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $this->schema->shouldReceive('getConnection')->once()->andReturn($connection);
        $this->schema->shouldReceive('hasTable')->once()->with('users')->andReturn(true);
        $this->database->shouldReceive('selectOne')->once()->andReturn((object) [
            'type' => "enum('aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'extra' => 'INVISIBLE',
            'generation_expression' => '',
            'srs_id' => null,
        ]);
        $this->database->shouldNotReceive('statement');

        try {
            $migration->up();
            self::fail('Unsupported column attributes must stop the migration.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('atribut yang tidak dapat dipertahankan', $exception->getMessage());
        }
    }

    public function test_status_down_rejects_pending_accounts_without_running_ddl(): void
    {
        $migration = $this->migration('2026_11_13_000001_add_pending_status_to_users_table.php');
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $this->schema->shouldReceive('getConnection')->once()->andReturn($connection);
        $this->schema->shouldReceive('hasTable')->once()->with('users')->andReturn(true);
        $this->database->shouldReceive('selectOne')->once()->andReturn($this->statusMetadata(true));
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('status', 'pending')->andReturnSelf();
        $query->shouldReceive('count')->once()->andReturn(3);
        $this->database->shouldReceive('table')->once()->with('users')->andReturn($query);
        $this->database->shouldNotReceive('statement');

        try {
            $migration->down();
            self::fail('Rollback must reject accounts that still use pending status.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('masih ada 3 akun', $exception->getMessage());
        }
    }

    public function test_status_down_requires_controlled_rollback_even_when_no_pending_accounts_exist(): void
    {
        $migration = $this->migration('2026_11_13_000001_add_pending_status_to_users_table.php');
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $this->schema->shouldReceive('getConnection')->once()->andReturn($connection);
        $this->schema->shouldReceive('hasTable')->once()->with('users')->andReturn(true);
        $this->database->shouldReceive('selectOne')->once()->andReturn($this->statusMetadata(true));
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('status', 'pending')->andReturnSelf();
        $query->shouldReceive('count')->once()->andReturn(0);
        $this->database->shouldReceive('table')->once()->with('users')->andReturn($query);
        $this->database->shouldNotReceive('statement');

        try {
            $migration->down();
            self::fail('Automatic rollback must remain disabled because the check is racy.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Rollback otomatis status akun dinonaktifkan', $exception->getMessage());
        }
    }

    public function test_pending_up_checks_all_parent_tables_before_any_ddl(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $this->mockParentPreflight(missingTable: 'guru');
        $this->database->shouldNotReceive('statement');
        $this->database->shouldNotReceive('select');

        try {
            $migration->up();
            self::fail('Missing parent tables must stop before table creation.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Tabel induk epkl.guru belum tersedia', $exception->getMessage());
        }
    }

    public function test_pending_up_creates_only_after_parent_preflight_succeeds(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $this->mockParentPreflight();
        $this->schema->shouldReceive('hasTable')->once()
            ->with('pending_account_verifications')->andReturn(false);
        $this->database->shouldReceive('statement')->once()->withArgs(
            static function (string $sql): bool {
                self::assertStringContainsString('CREATE TABLE `pending_account_verifications`', $sql);
                self::assertStringContainsString('ON DELETE CASCADE', $sql);
                self::assertStringContainsString('ON DELETE RESTRICT', $sql);
                self::assertStringContainsString('siswa_id` IS NOT NULL', $sql);
                self::assertStringContainsString('guru_id` IS NOT NULL', $sql);

                return true;
            },
        );

        $migration->up();
    }

    public function test_pending_up_rejects_existing_incompatible_table_without_ddl(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $this->mockParentPreflight();
        $this->schema->shouldReceive('hasTable')->once()
            ->with('pending_account_verifications')->andReturn(true);
        $this->schema->shouldReceive('getColumns')->once()->with('pending_account_verifications')->andReturn([]);
        $this->schema->shouldReceive('getForeignKeys')->once()->with('pending_account_verifications')->andReturn([]);
        $this->database->shouldReceive('select')->times(3)->andReturnUsing(
            fn (string $sql): array => match (true) {
                str_contains($sql, 'information_schema.COLUMNS') => $this->columnMetadata(),
                str_contains($sql, 'information_schema.STATISTICS') => $this->validIndexes(),
                default => [],
            },
        );
        $this->database->shouldNotReceive('statement');

        try {
            $migration->up();
            self::fail('An incompatible existing table must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString(
                'Tabel epkl_migration_create_test.pending_account_verifications tidak dapat direkonsiliasi',
                $exception->getMessage(),
            );
            self::assertStringContainsString('Daftar kolom tidak sama', $exception->getMessage());
            self::assertStringContainsString('tidak sesuai', $exception->getMessage());
            self::assertStringNotContainsString('SQLSTATE', $exception->getMessage());
        }
    }

    public function test_pending_down_refuses_to_drop_even_a_matching_external_replacement(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $connection = Mockery::mock();
        $connection->shouldReceive('getDatabaseName')->once()->andReturn('epkl_migration_create_test');
        $this->schema->shouldReceive('getConnection')->once()->andReturn($connection);
        $this->schema->shouldReceive('hasTable')->once()
            ->with('pending_account_verifications')->andReturn(true);
        $this->schema->shouldNotReceive('drop');
        $this->database->shouldNotReceive('statement');

        try {
            $migration->down();
            self::fail('Migration cannot establish table provenance from schema equality.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('membuktikan provenance/kepemilikan tabel', $exception->getMessage());
            self::assertStringContainsString('epkl_migration_create_test.pending_account_verifications', $exception->getMessage());
        }
    }

    #[DataProvider('xorExpressions')]
    public function test_profile_check_accepts_exact_xor_and_evaluates_all_presence_combinations(
        string $expression,
    ): void {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');

        self::assertTrue($this->invoke($migration, 'isExactlyOneProfileCheck', $expression));
        $tree = $this->invoke($migration, 'parseProfileCheck', $expression);

        self::assertSame(false, $this->invoke($migration, 'evaluateProfileCheck', $tree, false, false));
        self::assertSame(true, $this->invoke($migration, 'evaluateProfileCheck', $tree, false, true));
        self::assertSame(true, $this->invoke($migration, 'evaluateProfileCheck', $tree, true, false));
        self::assertSame(false, $this->invoke($migration, 'evaluateProfileCheck', $tree, true, true));
    }

    public static function xorExpressions(): array
    {
        return [
            'student first' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(siswa_id IS NULL AND guru_id IS NOT NULL)',
            ],
            'teacher first with varied parentheses and whitespace' => [
                " ( ( guru_id IS NOT NULL AND siswa_id IS NULL ) OR\n"
                    ." ( guru_id IS NULL AND siswa_id IS NOT NULL ) ) ",
            ],
        ];
    }

    #[DataProvider('invalidCheckExpressions')]
    public function test_profile_check_rejects_non_xor_or_unsupported_expressions(string $expression): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');

        self::assertFalse($this->invoke($migration, 'isExactlyOneProfileCheck', $expression));
    }

    public static function invalidCheckExpressions(): array
    {
        return [
            'both null and both populated' => [
                '(siswa_id IS NULL AND guru_id IS NULL) OR '
                    .'(siswa_id IS NOT NULL AND guru_id IS NOT NULL)',
            ],
            'only student required' => ['siswa_id IS NOT NULL'],
            'only teacher required' => ['guru_id IS NOT NULL'],
            'numeric role dependency' => [
                '(role_id = 2 AND siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(role_id = 3 AND siswa_id IS NULL AND guru_id IS NOT NULL)',
            ],
            'unsupported operator' => ['siswa_id IS NOT NULL XOR guru_id IS NOT NULL'],
            'unbalanced parentheses' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL OR siswa_id IS NULL AND guru_id IS NOT NULL',
            ],
            'trailing token' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(siswa_id IS NULL AND guru_id IS NOT NULL) unexpected',
            ],
        ];
    }

    public function test_existing_pending_table_with_role_bound_check_is_reported_as_mismatch(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            $this->validIndexes(),
            $this->validForeignKeys(),
            [(object) [
                'CONSTRAINT_NAME' => 'chk_pending_role_master',
                'CHECK_CLAUSE' => '(role_id = 2 AND siswa_id IS NOT NULL) OR '
                    .'(role_id = 3 AND guru_id IS NOT NULL)',
                'ENFORCED' => 'YES',
            ]],
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );

        self::assertContains(
            'CHECK chk_pending_role_master harus enforced dan menegakkan XOR profil tanpa role_id.',
            $errors,
        );
    }

    public function test_legacy_pending_schema_is_accepted_only_for_controlled_reconciliation(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $indexes = $this->validIndexes();
        $legacyIndexNames = [
            'uq_pending_account_verifications_user' => 'uq_pending_user',
            'idx_pending_account_verifications_role' => 'fk_pending_role',
            'uq_pending_account_verifications_siswa' => 'uq_pending_siswa',
            'uq_pending_account_verifications_guru' => 'uq_pending_guru',
            'uq_pending_account_verifications_token' => 'uq_pending_token',
        ];
        foreach ($indexes as $index) {
            if (isset($legacyIndexNames[$index->INDEX_NAME])) {
                $index->INDEX_NAME = $legacyIndexNames[$index->INDEX_NAME];
            }
        }

        $foreignKeys = $this->validForeignKeys();
        $legacyForeignKeyNames = [
            'fk_pending_account_verifications_user' => 'fk_pending_user',
            'fk_pending_account_verifications_role' => 'fk_pending_role',
            'fk_pending_account_verifications_siswa' => 'fk_pending_siswa',
            'fk_pending_account_verifications_guru' => 'fk_pending_guru',
        ];
        foreach ($foreignKeys as $index => $foreignKey) {
            $foreignKeys[$index]['name'] = $legacyForeignKeyNames[$foreignKey['name']];
        }

        $legacyCheck = (object) [
            'CONSTRAINT_NAME' => 'chk_pending_role_master',
            'CHECK_CLAUSE' => '(((`role_id` = 2) and (`guru_id` is not null) and (`siswa_id` is null)) or '
                .'((`role_id` = 3) and (`siswa_id` is not null) and (`guru_id` is null)))',
            'ENFORCED' => 'YES',
        ];

        self::assertSame(
            [],
            $this->schemaErrors($migration, $this->columnMetadata(), $indexes, $foreignKeys, [$legacyCheck], true),
        );
        self::assertNotSame(
            [],
            $this->schemaErrors($migration, $this->columnMetadata(), $indexes, $foreignKeys, [$legacyCheck]),
        );
    }

    public function test_legacy_reconciliation_plan_preserves_indexes_foreign_keys_and_rows(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $indexes = array_map(
            fn (object $index): object => (object) ['INDEX_NAME' => match ($index->INDEX_NAME) {
                'uq_pending_account_verifications_user' => 'uq_pending_user',
                'idx_pending_account_verifications_role' => 'fk_pending_role',
                'uq_pending_account_verifications_siswa' => 'uq_pending_siswa',
                'uq_pending_account_verifications_guru' => 'uq_pending_guru',
                'uq_pending_account_verifications_token' => 'uq_pending_token',
                default => $index->INDEX_NAME,
            }],
            $this->validIndexes(),
        );
        $foreignKeys = array_map(
            static fn (array $foreignKey): object => (object) [
                'CONSTRAINT_NAME' => match ($foreignKey['name']) {
                    'fk_pending_account_verifications_user' => 'fk_pending_user',
                    'fk_pending_account_verifications_role' => 'fk_pending_role',
                    'fk_pending_account_verifications_siswa' => 'fk_pending_siswa',
                    'fk_pending_account_verifications_guru' => 'fk_pending_guru',
                },
            ],
            $this->validForeignKeys(),
        );
        $legacyCheck = (object) [
            'CONSTRAINT_NAME' => 'chk_pending_role_master',
            'CHECK_CLAUSE' => '(((`role_id` = 2) and (`guru_id` is not null) and (`siswa_id` is null)) or '
                .'((`role_id` = 3) and (`siswa_id` is not null) and (`guru_id` is null)))',
            'ENFORCED' => 'YES',
        ];

        $statements = $this->invoke($migration, 'reconciliationStatements', $indexes, $foreignKeys, [$legacyCheck]);

        self::assertCount(6, $statements);
        self::assertStringContainsString('RENAME INDEX', $statements[0]);
        self::assertStringContainsString('DROP FOREIGN KEY `fk_pending_user`', $statements[1]);
        self::assertStringContainsString('ADD CONSTRAINT `fk_pending_account_verifications_user`', $statements[1]);
        self::assertStringContainsString('DROP CHECK `chk_pending_role_master`', $statements[5]);
        self::assertStringContainsString('CHECK ((`siswa_id` IS NOT NULL AND `guru_id` IS NULL)', $statements[5]);
        self::assertStringNotContainsString('role_id', $statements[5]);
        self::assertStringNotContainsString('DROP TABLE', implode(' ', $statements));
        self::assertStringNotContainsString('DELETE FROM', implode(' ', $statements));
    }

    public function test_reconciliation_plan_is_noop_after_target_schema_is_reached(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $foreignKeys = array_map(
            static fn (array $foreignKey): object => (object) [
                'CONSTRAINT_NAME' => $foreignKey['name'],
            ],
            $this->validForeignKeys(),
        );

        self::assertSame(
            [],
            $this->invoke(
                $migration,
                'reconciliationStatements',
                $this->validIndexes(),
                $foreignKeys,
                [$this->validCheck()],
            ),
        );
    }

    public function test_pending_migration_rerun_with_target_schema_does_not_issue_ddl(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $foreignKeys = array_map(
            static fn (array $foreignKey): array => array_replace(
                $foreignKey,
                ['foreign_schema' => 'epkl_migration_create_test'],
            ),
            $this->validForeignKeys(),
        );
        $this->mockParentPreflight();
        $this->schema->shouldReceive('hasTable')->once()
            ->with('pending_account_verifications')->andReturn(true);
        $this->schema->shouldReceive('getColumns')->twice()
            ->with('pending_account_verifications')->andReturn($this->validColumns());
        $this->schema->shouldReceive('getForeignKeys')->twice()
            ->with('pending_account_verifications')->andReturn($foreignKeys);
        $this->database->shouldReceive('select')->times(9)->andReturnUsing(
            fn (string $sql): array => match (true) {
                str_contains($sql, 'information_schema.COLUMNS') => $this->columnMetadata(),
                str_contains($sql, 'SEQ_IN_INDEX') => $this->validIndexes(),
                str_contains($sql, 'DISTINCT INDEX_NAME') => array_map(
                    fn (object $index): object => (object) ['INDEX_NAME' => $index->INDEX_NAME],
                    $this->validIndexes(),
                ),
                str_contains($sql, 'CONSTRAINT_TYPE') => [$this->validCheck()],
                str_contains($sql, 'REFERENCED_TABLE_NAME') => array_map(
                    static fn (array $foreignKey): object => (object) [
                        'CONSTRAINT_NAME' => $foreignKey['name'],
                    ],
                    $foreignKeys,
                ),
                default => [],
            },
        );
        $this->database->shouldNotReceive('statement');

        self::assertNull($migration->up());
    }

    public function test_pending_schema_requires_expected_unique_indexes_and_foreign_key_rules(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $checks = [(object) [
            'CONSTRAINT_NAME' => 'chk_pending_role_master',
            'CHECK_CLAUSE' => '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                .'(siswa_id IS NULL AND guru_id IS NOT NULL)',
            'ENFORCED' => 'YES',
        ]];

        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            array_values(array_filter(
                $this->validIndexes(),
                static fn (object $index): bool => $index->INDEX_NAME !== 'uq_pending_account_verifications_token',
            )),
            $this->validForeignKeys(),
            $checks,
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );
        self::assertContains(
            'Primary key, unique index, atau index biasa tidak sesuai struktur target.',
            $errors,
        );

        $foreignKeys = $this->validForeignKeys();
        $foreignKeys[0]['on_delete'] = 'restrict';
        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            $this->validIndexes(),
            $foreignKeys,
            $checks,
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );
        self::assertContains(
            'Foreign key, tabel/kolom tujuan, atau aturan ON DELETE/ON UPDATE tidak sesuai.',
            $errors,
        );

        $indexes = $this->validIndexes();
        $indexes[0]->SUB_PART = 2;
        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            $indexes,
            $this->validForeignKeys(),
            $checks,
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );
        self::assertContains(
            'Primary key, unique index, atau index biasa tidak sesuai struktur target.',
            $errors,
        );
    }

    #[DataProvider('invalidColumnMetadata')]
    public function test_pending_schema_rejects_unexpected_column_metadata(string $attribute): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $metadata = $this->columnMetadata();
        $token = array_values(array_filter(
            $metadata,
            static fn (object $column): bool => $column->COLUMN_NAME === 'token_hash',
        ))[0];

        match ($attribute) {
            'collation' => $token->COLLATION_NAME = 'utf8mb4_bin',
            'comment' => $token->COLUMN_COMMENT = 'unexpected',
            'generated' => $token->GENERATION_EXPRESSION = 'lower(token_hash)',
            'extra' => $token->EXTRA = 'INVISIBLE',
            'column type' => $token->COLUMN_TYPE = 'char(63)',
            'srs id' => $token->SRS_ID = 4326,
        };

        $errors = $this->schemaErrors($migration, $metadata);

        self::assertContains(
            'Atribut metadata information_schema.COLUMNS untuk token_hash tidak sesuai '
                .'(COLUMN_TYPE, nullability, default, EXTRA, generation, comment, charset/collation, atau SRS_ID).',
            $errors,
        );
    }

    public static function invalidColumnMetadata(): array
    {
        return [
            'collation mismatch' => ['collation'],
            'unexpected comment' => ['comment'],
            'generated column' => ['generated'],
            'unexpected extra attribute' => ['extra'],
            'column type mismatch' => ['column type'],
            'unexpected spatial reference id' => ['srs id'],
        ];
    }

    public function test_index_metadata_rejects_wrong_sequences_duplicates_order_and_counts(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $valid = $this->validIndexes();

        $wrongSequence = $this->validIndexes();
        $wrongSequence[1]->SEQ_IN_INDEX = 2;
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $wrongSequence, $this->expectedIndexes()));

        $duplicateSequence = $this->validIndexes();
        $duplicate = clone $duplicateSequence[1];
        $duplicate->COLUMN_NAME = 'role_id';
        $duplicateSequence[] = $duplicate;
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $duplicateSequence, $this->expectedIndexes()));

        $wrongColumnOrder = $this->validIndexes();
        $wrongColumnOrder[1]->COLUMN_NAME = 'role_id';
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $wrongColumnOrder, $this->expectedIndexes()));

        $wrongColumnCount = $this->validIndexes();
        $wrongColumnCount[] = $this->indexRow('uq_pending_user', 2, 'role_id', 0);
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $wrongColumnCount, $this->expectedIndexes()));

        $extraIndex = $this->validIndexes();
        $extraIndex[] = $this->indexRow('unexpected_index', 1, 'expires_at', 1);
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $extraIndex, $this->expectedIndexes()));

        $missingIndex = array_values(array_filter(
            $valid,
            static fn (object $index): bool => $index->INDEX_NAME !== 'uq_pending_account_verifications_token',
        ));
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $missingIndex, $this->expectedIndexes()));

        $compoundExpected = [['compound', ['user_id', 'role_id'], false, false]];
        $compoundIndexes = [
            $this->indexRow('compound', 1, 'user_id', 1),
            $this->indexRow('compound', 2, 'role_id', 1),
        ];
        self::assertTrue($this->invoke($migration, 'sameIndexDefinitions', $compoundIndexes, $compoundExpected));

        $reversedColumns = [
            $this->indexRow('compound', 1, 'role_id', 1),
            $this->indexRow('compound', 2, 'user_id', 1),
        ];
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $reversedColumns, $compoundExpected));

        $missingPosition = [
            $this->indexRow('compound', 1, 'user_id', 1),
            $this->indexRow('compound', 3, 'role_id', 1),
        ];
        self::assertFalse($this->invoke($migration, 'sameIndexDefinitions', $missingPosition, $compoundExpected));
    }

    public function test_valid_pending_table_metadata_has_no_schema_errors(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');

        self::assertSame(
            [],
            $this->invoke(
                $migration,
                'schemaErrors',
                $this->validColumns(),
                $this->columnMetadata(),
                $this->validIndexes(),
                $this->validForeignKeys(),
                [$this->validCheck()],
                'InnoDB',
                'utf8mb4_unicode_ci',
                'epkl',
            ),
        );
    }

    public function test_pending_schema_rejects_index_and_foreign_key_names_that_differ(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $indexes = $this->validIndexes();
        $indexes[1]->INDEX_NAME = 'uq_pending_user';

        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            $indexes,
            $this->validForeignKeys(),
            [$this->validCheck()],
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );
        self::assertContains(
            'Primary key, unique index, atau index biasa tidak sesuai struktur target.',
            $errors,
        );

        $foreignKeys = $this->validForeignKeys();
        $foreignKeys[0]['name'] = 'pending_user_foreign';
        $errors = $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $this->columnMetadata(),
            $this->validIndexes(),
            $foreignKeys,
            [$this->validCheck()],
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
        );
        self::assertContains(
            'Foreign key, tabel/kolom tujuan, atau aturan ON DELETE/ON UPDATE tidak sesuai.',
            $errors,
        );
    }

    public function test_existing_table_column_metadata_failure_stops_without_create_ddl(): void
    {
        $migration = $this->migration('2026_11_01_163237_create_pending_account_verifications_table.php');
        $this->mockParentPreflight();
        $this->schema->shouldReceive('hasTable')->once()
            ->with('pending_account_verifications')->andReturn(true);
        $this->schema->shouldReceive('getColumns')->once()->with('pending_account_verifications')->andReturn($this->validColumns());
        $this->schema->shouldReceive('getIndexes')->never();
        $this->schema->shouldReceive('getForeignKeys')->once()->with('pending_account_verifications')->andReturn($this->validForeignKeys());
        $this->database->shouldReceive('selectOne')->andReturnUsing(
            static fn (string $sql): object => (object) [
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8mb4_unicode_ci',
            ],
        );
        $indexes = $this->validIndexes();
        $metadata = $this->columnMetadata();
        foreach ($metadata as $column) {
            if ($column->COLUMN_NAME === 'token_hash') {
                $column->COLLATION_NAME = 'utf8mb4_bin';
            }
        }
        $checks = [(object) [
            'CONSTRAINT_NAME' => 'chk_pending_role_master',
            'CHECK_CLAUSE' => '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                .'(siswa_id IS NULL AND guru_id IS NOT NULL)',
            'ENFORCED' => 'YES',
        ]];
        $this->database->shouldReceive('select')->times(3)->andReturnUsing(
            static fn (string $sql): array => match (true) {
                str_contains($sql, 'information_schema.COLUMNS') => $metadata,
                str_contains($sql, 'information_schema.STATISTICS') => (static function () use ($sql, $indexes): array {
                    self::assertStringContainsString('INDEX_NAME', $sql);
                    self::assertStringContainsString('NON_UNIQUE', $sql);
                    self::assertStringContainsString('SEQ_IN_INDEX', $sql);
                    self::assertStringContainsString('COLUMN_NAME', $sql);
                    self::assertStringContainsString('EXPRESSION', $sql);
                    self::assertStringContainsString('IS_VISIBLE', $sql);
                    self::assertStringContainsString('SUB_PART', $sql);
                    self::assertStringContainsString('INDEX_TYPE', $sql);
                    self::assertStringContainsString('COLLATION', $sql);
                    self::assertStringContainsString('INDEX_COMMENT', $sql);
                    self::assertStringNotContainsString('PARSER', $sql);

                    return $indexes;
                })(),
                default => $checks,
            },
        );
        $this->database->shouldNotReceive('statement');

        try {
            $migration->up();
            self::fail('Mismatched column metadata must reject the existing table.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Atribut metadata information_schema.COLUMNS', $exception->getMessage());
        }
    }

    private function schemaErrors(
        object $migration,
        array $metadata,
        ?array $indexes = null,
        ?array $foreignKeys = null,
        ?array $checks = null,
        bool $allowLegacyDefinitions = false,
    ): array
    {
        return $this->invoke(
            $migration,
            'schemaErrors',
            $this->validColumns(),
            $metadata,
            $indexes ?? $this->validIndexes(),
            $foreignKeys ?? $this->validForeignKeys(),
            $checks ?? [$this->validCheck()],
            'InnoDB',
            'utf8mb4_unicode_ci',
            'epkl',
            $allowLegacyDefinitions,
        );
    }

    private function expectedIndexes(): array
    {
        return [
            ['PRIMARY', ['id'], true, true],
            ['uq_pending_account_verifications_user', ['user_id'], true, false],
            ['idx_pending_account_verifications_role', ['role_id'], false, false],
            ['uq_pending_account_verifications_siswa', ['siswa_id'], true, false],
            ['uq_pending_account_verifications_guru', ['guru_id'], true, false],
            ['uq_pending_account_verifications_token', ['token_hash'], true, false],
        ];
    }

    private function validCheck(): object
    {
        return (object) [
            'CONSTRAINT_NAME' => 'chk_pending_role_master',
            'CHECK_CLAUSE' => '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                .'(siswa_id IS NULL AND guru_id IS NOT NULL)',
            'ENFORCED' => 'YES',
        ];
    }

    private function statusMetadata(bool $pendingEnum): object
    {
        return (object) [
            'type' => $pendingEnum
                ? "enum('pending','aktif','nonaktif','ditangguhkan')"
                : "enum('aktif','nonaktif','ditangguhkan')",
            'nullable' => 'NO',
            'default' => 'aktif',
            'character_set' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '',
            'extra' => '',
            'generation_expression' => '',
            'srs_id' => null,
        ];
    }

    private function mockParentPreflight(?string $missingTable = null): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('getDatabaseName')->atLeast()->once()->andReturn('epkl_migration_create_test');
        $this->schema->shouldReceive('getConnection')->atLeast()->times(2)->andReturn($connection);

        $this->database->shouldReceive('selectOne')->andReturnUsing(
            static fn (string $sql): object => str_contains($sql, 'VERSION()')
                ? (object) ['version' => '8.0.30']
                : (object) ['ENGINE' => 'InnoDB', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci'],
        );

        foreach (['users', 'roles', 'siswa', 'guru'] as $table) {
            $exists = $table !== $missingTable;
            $this->schema->shouldReceive('hasTable')->once()->with($table)->andReturn($exists);
            if ($exists) {
                $this->schema->shouldReceive('getColumns')->once()->with($table)->andReturn($this->parentColumns());
                $this->schema->shouldReceive('getIndexes')->once()->with($table)->andReturn($this->parentIndexes());
            }
        }
    }

    private function parentColumns(): array
    {
        return [[
            'name' => 'id',
            'type' => 'bigint unsigned',
            'nullable' => false,
            'auto_increment' => true,
        ]];
    }

    private function parentIndexes(): array
    {
        return [[
            'columns' => ['id'],
            'unique' => true,
            'primary' => true,
        ]];
    }

    private function validColumns(): array
    {
        return [
            ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'auto_increment' => true],
            ['name' => 'user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'auto_increment' => false],
            ['name' => 'role_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'auto_increment' => false],
            ['name' => 'siswa_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'auto_increment' => false],
            ['name' => 'guru_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'auto_increment' => false],
            ['name' => 'token_hash', 'type' => 'char(64)', 'nullable' => false, 'default' => null, 'auto_increment' => false],
            ['name' => 'expires_at', 'type' => 'datetime', 'nullable' => false, 'default' => null, 'auto_increment' => false],
            ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP', 'auto_increment' => false],
        ];
    }

    private function columnMetadata(): array
    {
        $definitions = [
            ['id', 'bigint unsigned', 'NO', null, 'auto_increment', '', '', null, null, null],
            ['user_id', 'bigint unsigned', 'NO', null, '', '', '', null, null, null],
            ['role_id', 'bigint unsigned', 'NO', null, '', '', '', null, null, null],
            ['siswa_id', 'bigint unsigned', 'YES', null, '', '', '', null, null, null],
            ['guru_id', 'bigint unsigned', 'YES', null, '', '', '', null, null, null],
            ['token_hash', 'char(64)', 'NO', null, '', '', '', 'utf8mb4', 'utf8mb4_unicode_ci', null],
            ['expires_at', 'datetime', 'NO', null, '', '', '', null, null, null],
            ['created_at', 'timestamp', 'YES', 'CURRENT_TIMESTAMP', 'DEFAULT_GENERATED', '', '', null, null, null],
        ];

        return array_map(
            static fn (array $column): object => (object) array_combine(
                [
                    'COLUMN_NAME',
                    'COLUMN_TYPE',
                    'IS_NULLABLE',
                    'COLUMN_DEFAULT',
                    'EXTRA',
                    'GENERATION_EXPRESSION',
                    'COLUMN_COMMENT',
                    'CHARACTER_SET_NAME',
                    'COLLATION_NAME',
                    'SRS_ID',
                ],
                $column,
            ),
            $definitions,
        );
    }

    private function validIndexes(): array
    {
        return [
            $this->indexRow('PRIMARY', 1, 'id', 0),
            $this->indexRow('uq_pending_account_verifications_user', 1, 'user_id', 0),
            $this->indexRow('idx_pending_account_verifications_role', 1, 'role_id', 1),
            $this->indexRow('uq_pending_account_verifications_siswa', 1, 'siswa_id', 0),
            $this->indexRow('uq_pending_account_verifications_guru', 1, 'guru_id', 0),
            $this->indexRow('uq_pending_account_verifications_token', 1, 'token_hash', 0),
        ];
    }

    private function indexRow(string $name, int $sequence, string $column, int $nonUnique): object
    {
        return (object) [
            'INDEX_NAME' => $name,
            'NON_UNIQUE' => $nonUnique,
            'SEQ_IN_INDEX' => $sequence,
            'COLUMN_NAME' => $column,
            'SUB_PART' => null,
            'INDEX_TYPE' => 'BTREE',
            'IS_VISIBLE' => 'YES',
            'EXPRESSION' => null,
            'COLLATION' => 'A',
            'INDEX_COMMENT' => '',
        ];
    }

    private function validForeignKeys(): array
    {
        return [
            ['name' => 'fk_pending_account_verifications_user', 'columns' => ['user_id'], 'foreign_schema' => 'epkl', 'foreign_table' => 'users', 'foreign_columns' => ['id'], 'on_update' => 'NO ACTION', 'on_delete' => 'CASCADE'],
            ['name' => 'fk_pending_account_verifications_role', 'columns' => ['role_id'], 'foreign_schema' => 'epkl', 'foreign_table' => 'roles', 'foreign_columns' => ['id'], 'on_update' => 'NO ACTION', 'on_delete' => 'RESTRICT'],
            ['name' => 'fk_pending_account_verifications_siswa', 'columns' => ['siswa_id'], 'foreign_schema' => 'epkl', 'foreign_table' => 'siswa', 'foreign_columns' => ['id'], 'on_update' => 'NO ACTION', 'on_delete' => 'CASCADE'],
            ['name' => 'fk_pending_account_verifications_guru', 'columns' => ['guru_id'], 'foreign_schema' => 'epkl', 'foreign_table' => 'guru', 'foreign_columns' => ['id'], 'on_update' => 'NO ACTION', 'on_delete' => 'CASCADE'],
        ];
    }
}
