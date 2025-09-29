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
            $table->dropColumn('lending_asuransi_perusahaan');
            $table
                ->foreignId('lending_asuransi_perusahaan')
                ->nullable()
                ->after('lending_tanggal_rencana_takeover')
                ->constrained('perusahaan_asuransis')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropForeign(['lending_asuransi_perusahaan']);
            $table->dropColumn('lending_asuransi_perusahaan');
            $table
                ->string('lending_asuransi_perusahaan')
                ->after('lending_tanggal_rencana_takeover');
        });
    }
};
