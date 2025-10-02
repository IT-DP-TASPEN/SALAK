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
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table
                ->foreignId('funding_petugas')
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table->dropForeign(['funding_petugas']);
            $table->dropColumn('funding_petugas');
        });
    }
};
