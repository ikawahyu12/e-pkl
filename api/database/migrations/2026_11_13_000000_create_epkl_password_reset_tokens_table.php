<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException(
                'Tabel epkl.users belum tersedia untuk foreign key password reset.'
            );
        }

        if (Schema::hasTable('epkl_password_reset_tokens')) {
            $errors = $this->existingTableSchemaErrors();
            $details = $errors === []
                ? 'Struktur tabel cocok, tetapi migration tidak akan mengadopsi tabel yang '
                    .'belum tercatat karena rollback dapat menghapus tabel yang bukan dibuatnya.'
                : "Struktur tabel tidak cocok:\n- ".implode("\n- ", $errors);

            throw new \RuntimeException(
                "Tabel epkl_password_reset_tokens sudah ada tetapi migration ini belum tercatat. "
                .$details.' Rekonsiliasi ledger harus dilakukan secara terpisah.'
            );
        }

        Schema::create('epkl_password_reset_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('role', 20);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epkl_password_reset_tokens');
    }

    private function existingTableSchemaErrors(): array
    {
        $errors = [];
        $columns = collect(Schema::getColumns('epkl_password_reset_tokens'))->keyBy('name');
        $requiredColumns = [
            'id' => ['bigint unsigned', false, null, true],
            'user_id' => ['bigint unsigned', false, null, false],
            'email' => ['varchar(255)', false, null, false],
            'role' => ['varchar(20)', false, null, false],
            'token_hash' => ['varchar(64)', false, null, false],
            'expires_at' => ['timestamp', false, null, false],
            'created_at' => ['timestamp', false, 'current_timestamp', false],
        ];

        foreach ($requiredColumns as $name => [$expectedType, $nullable, $default, $autoIncrement]) {
            $column = $columns->get($name);

            if (! $column) {
                $errors[] = "Kolom $name tidak tersedia.";

                continue;
            }

            $actualDefault = $column['default'] === null
                ? null
                : strtolower(trim((string) $column['default']));
            if (preg_match('/^(current_timestamp|now)\s*(?:\(\s*\d*\s*\))?$/i', (string) $actualDefault) === 1) {
                $actualDefault = 'current_timestamp';
            }

            if (
                strtolower($column['type']) !== $expectedType
                || $column['nullable'] !== $nullable
                || $actualDefault !== $default
                || $column['auto_increment'] !== $autoIncrement
            ) {
                $errors[] = "Kolom $name tidak sesuai dengan tipe, panjang, nullable, default, atau AUTO_INCREMENT.";
            }
        }

        $requiredIndexes = [
            [['id'], true, true],
            [['token_hash'], true, false],
            [['expires_at'], false, false],
            [['user_id', 'created_at'], false, false],
        ];
        $actualIndexes = Schema::getIndexes('epkl_password_reset_tokens');

        foreach ($requiredIndexes as [$indexColumns, $unique, $primary]) {
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
                $errors[] = 'Indeks '.implode(', ', $indexColumns).' tidak sesuai.';
            }
        }

        $databaseName = Schema::getConnection()->getDatabaseName();
        $foreignKeyFound = false;

        foreach (Schema::getForeignKeys('epkl_password_reset_tokens') as $foreignKey) {
            $normalizeRule = static function (?string $rule): string {
                $rule = strtolower(trim((string) $rule));

                return $rule === 'no action' ? 'restrict' : $rule;
            };

            if (
                $foreignKey['columns'] === ['user_id']
                && strcasecmp((string) $foreignKey['foreign_schema'], $databaseName) === 0
                && $foreignKey['foreign_table'] === 'users'
                && $foreignKey['foreign_columns'] === ['id']
                && $normalizeRule($foreignKey['on_delete']) === 'cascade'
                && $normalizeRule($foreignKey['on_update']) === 'restrict'
            ) {
                $foreignKeyFound = true;

                break;
            }
        }

        if (! $foreignKeyFound) {
            $errors[] = 'Foreign key user_id ke users.id dengan ON DELETE CASCADE tidak sesuai.';
        }

        return $errors;
    }
};
