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
            $table->json('lending_angsuran_fasilitas_aktif')->nullable()->after('lending_jenis_pengajuan');
            $table->double('lending_dsr')->nullable()->after('lending_angsuran_fasilitas_aktif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropColumn(['lending_angsuran_fasilitas_aktif', 'lending_dsr']);
        });
    }
};
