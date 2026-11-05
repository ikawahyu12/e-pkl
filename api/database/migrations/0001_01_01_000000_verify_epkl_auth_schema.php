<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $requiredColumns = [
            'users' => ['id', 'role_id', 'username', 'email', 'password_hash', 'nama_tampilan', 'status'],
            'roles' => ['id', 'nama'],
            'guru' => ['id', 'user_id', 'nip', 'nama_lengkap', 'mata_pelajaran', 'status_bimbingan'],
            'siswa' => ['id', 'user_id', 'nisn', 'nis', 'nama_lengkap', 'kelas_id', 'status_siswa'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Tabel epkl.$table belum tersedia. Siapkan skema epkl sebelum menjalankan migrasi API.");
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Kolom epkl.$table.$column belum tersedia. Periksa skema epkl sebelum menjalankan migrasi API.");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The epkl business schema is managed outside this API repository.
    }
};
