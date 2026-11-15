<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORIGINAL_STATUS_TYPE = "enum('aktif','nonaktif','ditangguhkan')";

    private const PENDING_STATUS_TYPE = "enum('pending','aktif','nonaktif','ditangguhkan')";

    public function up(): void
    {
        $this->assertMySql();
        $status = $this->usersStatusMetadata(self::ORIGINAL_STATUS_TYPE);
        $definition = $this->statusDefinition($status);

        DB::statement(
            'ALTER TABLE `users` MODIFY `status` '.$definition
        );
    }

    public function down(): void
    {
        $this->assertMySql();
        $status = $this->usersStatusMetadata(self::PENDING_STATUS_TYPE);

        $pendingAccounts = (int) DB::table('users')->where('status', 'pending')->count();
        $this->assertNoPendingAccounts($pendingAccounts);
        $this->assertControlledRollbackRequired();
    }

    private function assertMySql(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Migration status akun E-PKL hanya mendukung MySQL.'
            );
        }
    }

    private function usersStatusMetadata(string $expectedType): object
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException('Tabel epkl.users belum tersedia.');
        }

        $status = DB::selectOne(
            'SELECT COLUMN_TYPE AS `type`, IS_NULLABLE AS `nullable`, COLUMN_DEFAULT AS `default`, '
                .'CHARACTER_SET_NAME AS `character_set`, COLLATION_NAME AS `collation`, '
                .'COLUMN_COMMENT AS `comment`, EXTRA AS `extra`, '
                .'GENERATION_EXPRESSION AS `generation_expression`, '
                .'SRS_ID AS `srs_id` '
                .'FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['users', 'status'],
        );

        if (! $status || ! $this->statusColumnMatches((array) $status, $expectedType)) {
            throw new \RuntimeException(
                'Kolom epkl.users.status tidak cocok atau memiliki atribut yang tidak dapat '
                .'dipertahankan dengan aman (tipe, nullability, default, charset/collation, '
                .'comment, generation, SRS, atau atribut EXTRA). Tidak ada perubahan dilakukan.'
            );
        }

        return $status;
    }

    private function statusColumnMatches(array $column, string $expectedType): bool
    {
        $characterSet = $column['character_set'] ?? null;
        $collation = $column['collation'] ?? null;

        return strtolower(preg_replace('/\s+/', '', (string) ($column['type'] ?? '')))
                === strtolower(preg_replace('/\s+/', '', $expectedType))
            && strtoupper((string) ($column['nullable'] ?? '')) === 'NO'
            && $this->normalizeDefault($column['default'] ?? null) === 'aktif'
            && is_string($characterSet)
            && preg_match('/^[a-z0-9_]+$/i', $characterSet) === 1
            && is_string($collation)
            && preg_match('/^[a-z0-9_]+$/i', $collation) === 1
            && is_string($column['comment'] ?? null)
            && ! str_contains((string) ($column['comment'] ?? ''), '\\')
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', (string) ($column['comment'] ?? '')) !== 1
            && trim((string) ($column['extra'] ?? '')) === ''
            && trim((string) ($column['generation_expression'] ?? '')) === ''
            && ($column['srs_id'] ?? null) === null;
    }

    private function statusDefinition(object $status): string
    {
        $comment = str_replace("'", "''", (string) $status->comment);

        return "ENUM('pending','aktif','nonaktif','ditangguhkan') "
            .'CHARACTER SET `'.$status->character_set.'` '
            .'COLLATE `'.$status->collation.'` NOT NULL DEFAULT \'aktif\' '
            ."COMMENT '$comment'";
    }

    private function normalizeDefault(mixed $default): ?string
    {
        return $default === null ? null : trim((string) $default, "'\"");
    }

    private function assertNoPendingAccounts(int $pendingAccounts): void
    {
        if ($pendingAccounts > 0) {
            throw new \RuntimeException(
                "Rollback ditolak: masih ada $pendingAccounts akun berstatus pending. "
                .'Aktifkan atau tangani akun tersebut sebelum menghapus nilai status pending.'
            );
        }
    }

    private function assertControlledRollbackRequired(): never
    {
        throw new \RuntimeException(
            'Rollback otomatis status akun dinonaktifkan: MySQL DDL melakukan implicit commit, '
            .'sehingga pemeriksaan akun pending tidak dapat dibuat atomik terhadap perubahan ENUM. '
            .'Hentikan seluruh penulisan aplikasi/registrasi dalam maintenance window, pastikan '
            .'tidak ada akun pending, lalu lakukan perubahan skema secara terkontrol.'
        );
    }
};
