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
            $table->decimal('lending_pelunasan_bunga', 15, 2)
                ->after('lending_pelunasan_pokok');
            $table->foreignId('lending_mitra_bayar_takeover')
                ->nullable()
                ->constrained('mitra_bayars')
                ->after('lending_tanggal_rencana_takeover');
            $table->string('lending_nama_koperasi_takeover')
                ->nullable()
                ->after('lending_mitra_bayar_takeover');
            $table->date('lending_tanggal_lahir_debitur')
                ->after('lending_nama_debitur');
            $table->string('lending_no_hp_debitur')
                ->after('lending_tanggal_lahir_debitur');
            $table->string('lending_notas_debitur')
                ->nullable()
                ->after('lending_no_hp_debitur');
            $table->enum('lending_sistem_bunga', ['Flate', 'Anuitas'])
                ->after('lending_tanggal_realisasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropColumn([
                'lending_pelunasan_bunga',
                'lending_mitra_bayar_takeover',
                'lending_nama_koperasi_takeover',
                'lending_tanggal_lahir_debitur',
                'lending_no_hp_debitur',
                'lending_notas_debitur',
                'lending_sistem_bunga',
            ]);
            $table->dropForeign(['lending_mitra_bayar_takeover']);
        });
    }
};
