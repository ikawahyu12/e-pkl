<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    if (! Schema::hasColumn('guru', 'kuota_bimbingan')) {
        Schema::table('guru', function (Blueprint $table) {
            $table->unsignedInteger('kuota_bimbingan')->default(0)->after('status_bimbingan');
        });
    }
}

public function down(): void
{
    if (Schema::hasColumn('guru', 'kuota_bimbingan')) {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn('kuota_bimbingan');
        });
    }
}
};
