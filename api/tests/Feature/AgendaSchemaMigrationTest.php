<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class AgendaSchemaMigrationTest extends TestCase
{
    private bool $usingDedicatedDatabase = false;

    private bool $holdsTestLock = false;

    private ?string $originalDefaultConnection = null;

    private bool $hadOriginalTestConnection = false;

    private ?array $originalTestConnection = null;

    private ?int $lockConnectionId = null;

    private array $parentIds = [];

    private array $ownedTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('RUN_AGENDA_SCHEMA_MIGRATION_TESTS') !== '1') {
            $this->markTestSkipped('Opt-in MySQL migration tests are disabled.');
        }

        $database = (string) getenv('AGENDA_SCHEMA_TEST_DATABASE');
        $processDatabase = (string) getenv('DB_DATABASE');
        $host = (string) getenv('AGENDA_SCHEMA_TEST_HOST');
        $port = (string) getenv('AGENDA_SCHEMA_TEST_PORT');
        $username = (string) getenv('AGENDA_SCHEMA_TEST_USERNAME');
        $expectedHostname = (string) getenv('AGENDA_SCHEMA_TEST_SERVER_HOSTNAME');
        $expectedServerVersion = (string) getenv('AGENDA_SCHEMA_TEST_SERVER_VERSION');
        $exclusiveTarget = (string) getenv('AGENDA_SCHEMA_TEST_EXCLUSIVE');
        if (
            $database !== 'epkl_merge_test'
            || $processDatabase !== 'epkl_merge_test'
            || in_array($database, ['epkl', 'epkl_integrasi_test'], true)
            || $host !== '127.0.0.1'
            || $port !== '3306'
            || $username === ''
            || $expectedHostname === ''
            || $expectedServerVersion !== '8.0.30'
            || $exclusiveTarget !== '1'
            || (getenv('DB_URL') !== false && getenv('DB_URL') !== '')
        ) {
            $this->markTestSkipped(
                'The explicitly approved and exclusive epkl_merge_test target is required.'
            );
        }

        $this->originalDefaultConnection = config('database.default');
        $connections = config('database.connections', []);
        $this->hadOriginalTestConnection = array_key_exists('agenda_schema_test', $connections);
        $this->originalTestConnection = $connections['agenda_schema_test'] ?? null;
        $this->usingDedicatedDatabase = true;

        try {
            config([
                'database.connections.agenda_schema_test' => [
                    'driver' => 'mysql',
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => (string) getenv('AGENDA_SCHEMA_TEST_PASSWORD'),
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => 'InnoDB',
                    'options' => [],
                ],
                'database.default' => 'agenda_schema_test',
            ]);
            DB::purge('agenda_schema_test');
            $connection = DB::connection('agenda_schema_test');
            $connection->getPdo();

            $identity = $connection->selectOne(
                'SELECT DATABASE() AS selected_database, @@hostname AS server_hostname, '
                .'@@port AS server_port, VERSION() AS server_version, '
                .'CURRENT_USER() AS authenticated_account'
            );
            if (
                $identity->selected_database !== 'epkl_merge_test'
                || $identity->server_hostname !== $expectedHostname
                || (string) $identity->server_port !== $port
                || (string) $identity->server_version !== $expectedServerVersion
                || ! str_starts_with((string) $identity->authenticated_account, $username.'@')
            ) {
                throw new RuntimeException(
                    'Connected MySQL identity did not match the explicitly approved epkl_merge_test target.'
                );
            }

            $lock = $connection->selectOne(
                "SELECT GET_LOCK('epkl_agenda_schema_migration_test', 0) AS acquired, "
                .'CONNECTION_ID() AS connection_id'
            );
            if ((int) $lock->acquired !== 1) {
                $this->markTestSkipped(
                    'Another Agenda schema migration test is using epkl_merge_test.'
                );
            }
            $this->holdsTestLock = true;
            $this->lockConnectionId = (int) $lock->connection_id;

            if ($connection->select('SHOW TABLES') !== []) {
                $this->markTestSkipped(
                    'epkl_merge_test must be empty before each migration scenario.'
                );
            }

            foreach (['guru', 'instansi', 'siswa', 'users'] as $table) {
                Schema::create($table, function (Blueprint $blueprint): void {
                    $blueprint->engine = 'InnoDB';
                    $blueprint->id();
                });
                $this->markOwnedTable($table);
                $this->parentIds[$table] = DB::table($table)->insertGetId([]);
            }
        } catch (Throwable $exception) {
            try {
                $this->cleanupDedicatedDatabase();
            } catch (Throwable $cleanupException) {
                throw new RuntimeException(
                    'Agenda schema test setup failed, and cleanup also failed: '
                    .$cleanupException->getMessage(),
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanupDedicatedDatabase();
        } finally {
            parent::tearDown();
        }
    }

    private function cleanupDedicatedDatabase(): void
    {
        if (! $this->usingDedicatedDatabase) {
            return;
        }

        try {
            if ($this->usingDedicatedDatabase && $this->holdsTestLock) {
                $connection = DB::connection('agenda_schema_test');
                $identity = $connection->selectOne(
                    'SELECT DATABASE() AS selected_database, @@hostname AS server_hostname, '
                    .'@@port AS server_port, VERSION() AS server_version, '
                    .'CURRENT_USER() AS authenticated_account, CONNECTION_ID() AS connection_id'
                );
                $sameLockSession = (int) $identity->connection_id === $this->lockConnectionId;
                $matchesTarget = $identity->selected_database === 'epkl_merge_test'
                    && $identity->server_hostname === getenv('AGENDA_SCHEMA_TEST_SERVER_HOSTNAME')
                    && (string) $identity->server_port === getenv('AGENDA_SCHEMA_TEST_PORT')
                    && (string) $identity->server_version === getenv('AGENDA_SCHEMA_TEST_SERVER_VERSION')
                    && str_starts_with(
                        (string) $identity->authenticated_account,
                        getenv('AGENDA_SCHEMA_TEST_USERNAME').'@'
                    );

                if ($sameLockSession && $matchesTarget) {
                    foreach (array_reverse($this->ownedTables) as $table) {
                        Schema::connection('agenda_schema_test')->dropIfExists($table);
                    }
                }
            }
        } finally {
            try {
                if ($this->holdsTestLock) {
                    $connection = DB::connection('agenda_schema_test');
                    $currentConnectionId = (int) $connection->selectOne(
                        'SELECT CONNECTION_ID() AS connection_id'
                    )->connection_id;
                    if ($currentConnectionId !== $this->lockConnectionId) {
                        throw new RuntimeException(
                            'Cannot release the Agenda test lock from a different MySQL session.'
                        );
                    }

                    $released = $connection->selectOne(
                        "SELECT RELEASE_LOCK('epkl_agenda_schema_migration_test') AS released"
                    )->released;
                    if ((int) $released !== 1) {
                        throw new RuntimeException('The Agenda test advisory lock was not released.');
                    }
                }
            } finally {
                try {
                    DB::disconnect('agenda_schema_test');
                } finally {
                    try {
                        DB::purge('agenda_schema_test');
                    } finally {
                        try {
                            $this->restoreDatabaseConfiguration();
                        } finally {
                            $this->usingDedicatedDatabase = false;
                            $this->holdsTestLock = false;
                            $this->lockConnectionId = null;
                        }
                    }
                }
            }
        }
    }

    private function restoreDatabaseConfiguration(): void
    {
        try {
            config(['database.default' => $this->originalDefaultConnection]);
        } finally {
            $connections = config('database.connections', []);
            if ($this->hadOriginalTestConnection) {
                $connections['agenda_schema_test'] = $this->originalTestConnection;
            } else {
                unset($connections['agenda_schema_test']);
            }
            config(['database.connections' => $connections]);
        }
    }

    public function test_fresh_schema_and_retry_preserve_agenda_rows(): void
    {
        $this->runMigration();
        $agendaId = $this->insertAgenda();
        DB::table('agenda_kunjungan_siswa')->insert([
            'agenda_kunjungan_id' => $agendaId,
            'siswa_id' => $this->parentIds['siswa'],
        ]);

        $this->runMigration();

        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
        $this->assertDatabaseHas('agenda_kunjungan_siswa', [
            'agenda_kunjungan_id' => $agendaId,
            'siswa_id' => $this->parentIds['siswa'],
        ], 'agenda_schema_test');
        $this->assertCount(5, array_merge(
            Schema::getForeignKeys('agenda_kunjungan'),
            Schema::getForeignKeys('agenda_kunjungan_siswa')
        ));
    }

    public function test_migration_resumes_partial_ddl_and_retry_preserves_agenda_id(): void
    {
        $this->createAgendaTable(true, false, true);
        $agendaId = $this->insertAgenda(false);

        $this->runMigration();
        $this->runMigration();

        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
        $this->assertTrue(Schema::hasTable('agenda_kunjungan_siswa'));
        $this->assertTrue(Schema::hasColumn('agenda_kunjungan', 'jenis_pencatatan'));
        $this->assertDatabaseHas('agenda_kunjungan', [
            'id' => $agendaId,
            'jenis_pencatatan' => null,
        ], 'agenda_schema_test');
    }

    public function test_unmarked_partial_schema_is_rejected_without_creating_a_pivot(): void
    {
        $this->createAgendaTable(false, false, false);
        $agendaId = $this->insertAgenda(false);

        try {
            $this->runMigration();
            $this->fail('A partial unmarked schema must require manual reconciliation.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('skema agenda parsial', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasTable('agenda_kunjungan_siswa'));
        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
    }

    public function test_orphan_preflight_rejects_without_adding_columns_or_foreign_keys(): void
    {
        $this->createAgendaTable(false, false, false);
        $this->createPivotTable(false);
        $agendaId = DB::table('agenda_kunjungan')->insertGetId([
            'guru_id' => 999999,
            'instansi_id' => $this->parentIds['instansi'],
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'terjadwal',
        ]);

        try {
            $this->runMigration();
            $this->fail('Orphan references must be rejected before schema changes.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Referensi agenda yatim', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('agenda_kunjungan', 'alasan_pembatalan'));
        $this->assertSame([], Schema::getForeignKeys('agenda_kunjungan'));
        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
    }

    public function test_canceled_legacy_rows_are_rejected_before_metadata_changes(): void
    {
        $this->createAgendaTable(false, false, false);
        $this->createPivotTable(false);
        $agendaId = DB::table('agenda_kunjungan')->insertGetId([
            'guru_id' => $this->parentIds['guru'],
            'instansi_id' => $this->parentIds['instansi'],
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'dibatalkan',
        ]);

        try {
            $this->runMigration();
            $this->fail('Legacy canceled agendas without audit metadata must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('status dibatalkan', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('agenda_kunjungan', 'alasan_pembatalan'));
        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
    }

    public function test_legacy_pivot_cascade_is_replaced_without_losing_rows(): void
    {
        $this->createAgendaTable(false, true, false);
        $this->createPivotTable(true);
        $agendaId = DB::table('agenda_kunjungan')->insertGetId([
            'guru_id' => $this->parentIds['guru'],
            'instansi_id' => $this->parentIds['instansi'],
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'terjadwal',
        ]);
        DB::table('agenda_kunjungan_siswa')->insert([
            'agenda_kunjungan_id' => $agendaId,
            'siswa_id' => $this->parentIds['siswa'],
        ]);

        $this->runMigration();

        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
        $this->assertDatabaseHas('agenda_kunjungan_siswa', [
            'agenda_kunjungan_id' => $agendaId,
            'siswa_id' => $this->parentIds['siswa'],
        ], 'agenda_schema_test');
        $agendaForeignKey = collect(Schema::getForeignKeys('agenda_kunjungan_siswa'))
            ->firstWhere('columns', ['agenda_kunjungan_id']);
        $this->assertContains(strtolower($agendaForeignKey['on_delete']), ['restrict', 'no action']);
    }

    public function test_down_refuses_to_drop_agenda_tables_or_history(): void
    {
        $this->runMigration();
        $agendaId = $this->insertAgenda();
        $migration = require database_path(
            'migrations/2026_11_15_140001_reconcile_agenda_kunjungan_schema.php'
        );

        try {
            $migration->down();
            $this->fail('Rollback must refuse to delete agenda tables and history.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Rollback otomatis ditolak', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('agenda_kunjungan'));
        $this->assertDatabaseHas('agenda_kunjungan', ['id' => $agendaId], 'agenda_schema_test');
    }

    private function runMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_11_15_140001_reconcile_agenda_kunjungan_schema.php'
        );
        $tables = ['agenda_kunjungan', 'agenda_kunjungan_siswa'];
        $existingTables = array_fill_keys(
            array_filter($tables, static fn (string $table): bool => Schema::hasTable($table)),
            true
        );

        try {
            $migration->up();
        } finally {
            foreach ($tables as $table) {
                if (! isset($existingTables[$table]) && Schema::hasTable($table)) {
                    $this->markOwnedTable($table);
                }
            }
        }
    }

    private function insertAgenda(bool $withCancellationColumns = true): int
    {
        $agenda = [
            'guru_id' => $this->parentIds['guru'],
            'instansi_id' => $this->parentIds['instansi'],
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'terjadwal',
            'catatan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if ($withCancellationColumns) {
            $agenda += [
                'alasan_pembatalan' => null,
                'dibatalkan_at' => null,
                'dibatalkan_oleh_user_id' => null,
            ];
        }
        if (Schema::hasColumn('agenda_kunjungan', 'jenis_pencatatan')) {
            $agenda['jenis_pencatatan'] = null;
        }

        return DB::table('agenda_kunjungan')->insertGetId($agenda);
    }

    private function createAgendaTable(
        bool $migrationOwned,
        bool $cancellationColumns,
        bool $foreignKeys
    ): void {
        Schema::create('agenda_kunjungan', function (Blueprint $table) use (
            $migrationOwned,
            $cancellationColumns,
            $foreignKeys
        ): void {
            $table->engine = 'InnoDB';
            if ($migrationOwned) {
                $table->comment('e-pkl:2026_11_15_140001:agenda');
            }
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('instansi_id');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status', 20)->default('terjadwal');
            $table->text('catatan')->nullable();
            if ($cancellationColumns) {
                $table->text('alasan_pembatalan')->nullable();
                $table->timestamp('dibatalkan_at')->nullable();
                $table->unsignedBigInteger('dibatalkan_oleh_user_id')->nullable();
            }
            $table->timestamps();

            if ($foreignKeys) {
                $table->foreign('guru_id')->references('id')->on('guru')->restrictOnDelete();
                $table->foreign('instansi_id')->references('id')->on('instansi')->restrictOnDelete();
                if ($cancellationColumns) {
                    $table->foreign('dibatalkan_oleh_user_id')
                        ->references('id')->on('users')->restrictOnDelete();
                }
            }
        });
        $this->markOwnedTable('agenda_kunjungan');
    }

    private function createPivotTable(bool $cascade): void
    {
        Schema::create('agenda_kunjungan_siswa', function (Blueprint $table) use ($cascade): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('agenda_kunjungan_id');
            $table->unsignedBigInteger('siswa_id');
            $table->primary(['agenda_kunjungan_id', 'siswa_id']);
            if ($cascade) {
                $table->foreign('agenda_kunjungan_id')
                    ->references('id')->on('agenda_kunjungan')->cascadeOnDelete();
            }
        });
        $this->markOwnedTable('agenda_kunjungan_siswa');
    }

    private function markOwnedTable(string $table): void
    {
        if (! in_array($table, $this->ownedTables, true)) {
            $this->ownedTables[] = $table;
        }
    }
}
