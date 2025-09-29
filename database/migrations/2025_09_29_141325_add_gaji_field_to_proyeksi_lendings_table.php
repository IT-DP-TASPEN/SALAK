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
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->decimal('lending_gaji_pokok', 15, 2)->after('lending_status_kerja');
            $table->decimal('lending_gaji_bersih', 15, 2)->after('lending_gaji_pokok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropColumn(['lending_gaji_pokok', 'lending_gaji_bersih']);
        });
    }
};
