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
            $table
                ->string('lending_tipe_pengajuan')
                ->nullable()
                ->after('lending_jenis_pengajuan');
            $table
                ->double('lending_pot_premi_percent', 5, 2)
                ->after('lending_pot_premi')
                ->default(0)
                ->comment('Persentase potongan premi dari plafond');
            $table
                ->double('lending_pot_premi_extra_percent', 5, 2)
                ->after('lending_pot_premi_extra')
                ->default(0)
                ->comment('Persentase potongan premi extra dari plafond');
            $table
                ->string('lending_asuransi_perusahaan')
                ->after('lending_tanggal_rencana_takeover');
            $table
                ->decimal('lending_bundling_bpjs', 15, 2)
                ->nullable()
                ->after('lending_bunga_muka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->dropColumn([
                'lending_tipe_pengajuan',
                'lending_pot_premi_percent',
                'lending_pot_premi_extra_percent',
                'lending_asuransi_perusahaan',
                'lending_bundling_bpjs',
            ]);
        });
    }
};
