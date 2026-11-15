<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException(
                'Baseline E-PKL tidak dapat dilanjutkan: tabel epkl.users belum tersedia.'
            );
        }

        $requiredColumns = [
            'id' => ['bigint unsigned', false, true],
            'role_id' => ['bigint unsigned', false, false],
            'username' => ['varchar(60)', true, false],
            'email' => ['varchar(150)', true, false],
            'password_hash' => ['varchar(255)', true, false],
            'nama_tampilan' => ['varchar(120)', false, false],
            'wajib_ganti_password' => ['tinyint(1)', false, false],
        ];

        $columns = collect(Schema::getColumns('users'))->keyBy('name');
        $errors = [];

        foreach ($requiredColumns as $name => [$type, $nullable, $autoIncrement]) {
            $column = $columns->get($name);

            if (
                ! $column
                || strtolower($column['type']) !== $type
                || $column['nullable'] !== $nullable
                || $column['auto_increment'] !== $autoIncrement
            ) {
                $errors[] = "epkl.users.$name harus bertipe $type"
                    .($nullable ? ' nullable' : ' NOT NULL')
                    .($autoIncrement ? ' AUTO_INCREMENT.' : '.');
            }
        }

        $statusColumn = $columns->get('status');

        $allowedStatusTypes = [
            "enum('aktif','nonaktif','ditangguhkan')",
            "enum('pending','aktif','nonaktif','ditangguhkan')",
        ];

        if (
            ! $statusColumn
            || ! in_array(strtolower($statusColumn['type']), $allowedStatusTypes, true)
            || $statusColumn['nullable'] !== false
            || $statusColumn['auto_increment'] !== false
        ) {
            $errors[] = 'epkl.users.status harus menggunakan ENUM status E-PKL '
                .'yang valid, NOT NULL, dan bukan AUTO_INCREMENT.';
        }


        $indexes = Schema::getIndexes('users');
        $hasIndex = static function (array $columns, bool $unique, bool $primary = false) use ($indexes): bool {
            foreach ($indexes as $index) {
                if (
                    $index['columns'] === $columns
                    && $index['unique'] === $unique
                    && $index['primary'] === $primary
                ) {
                    return true;
                }
            }

            return false;
        };

        foreach ([
            [['id'], true, true],
            [['username'], true, false],
            [['email'], true, false],
        ] as [$indexColumns, $unique, $primary]) {
            if (! $hasIndex($indexColumns, $unique, $primary)) {
                $errors[] = 'epkl.users memerlukan '
                    .($primary ? 'primary key' : 'unique index')
                    .' pada '.implode(', ', $indexColumns).'.';
            }
        }

        $hasRoleForeignKey = false;
        $databaseName = Schema::getConnection()->getDatabaseName();
        foreach (Schema::getForeignKeys('users') as $foreignKey) {
            if (
                $foreignKey['columns'] === ['role_id']
                && $foreignKey['foreign_schema'] === $databaseName
                && $foreignKey['foreign_table'] === 'roles'
                && $foreignKey['foreign_columns'] === ['id']
            ) {
                $hasRoleForeignKey = true;
                break;
            }
        }

        if (! $hasRoleForeignKey) {
            $errors[] = 'epkl.users.role_id harus memiliki foreign key ke epkl.roles.id.';
        }

        if ($errors !== []) {
            throw new \RuntimeException(
                "Skema baseline epkl.users tidak sesuai:\n- ".implode("\n- ", $errors)
            );
        }
    }

    public function down(): void
    {
        // The E-PKL users schema is managed outside this application.
    }
};
