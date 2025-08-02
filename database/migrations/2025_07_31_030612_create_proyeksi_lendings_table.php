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
        Schema::create('proyeksi_lendings', function (Blueprint $table) {
            $table->id();
            $table->date('lending_tanggal');
            $table->foreignId('lending_kantor')
                ->constrained('branch_offices')
                ->onDelete('cascade');
            $table->foreignId('lending_agent')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('lending_nama_debitur');
            $table->enum('lending_jenis_pengajuan', ['BARU', 'TOP UP'])->nullable();
            $table->enum('lending_produk', [
                'REGULER PRA PENSIUN',
                'REGULER PENSIUN',
                'PLATINUM',
                'PLATINUM PLUS',
                'DISKONTO',
                'KREDIT PEGAWAI AKTIF',
            ])->nullable();
            $table->enum('lending_sumber_pembayaran', [
                'GAJI',
                'TUNJ CUTI',
                'JASPROD',
                'THT PENSIUN'
            ])->nullable();
            $table->enum('lending_status_dapem', [
                'MUTASI DARI BANK LAIN',
                'SUDAH DI BANK DP TASPEN',
                'BARU PENSIUN KBY BANK DP TASPEN',
                'DAPEM BANPOT BANK MANTAP',
                'LAINNYA (BUKAN PENSIUNAN)'
            ])->nullable();
            $table->enum('lending_status_kerja', [
                'PENSIUN ASN',
                'PENSIUN DP TASPEN',
                'PEG TASPEN GROUP',
                'PEG BANK DP TASPEN',
                'PEG OJK',
                'PEG SWASTA LAINNYA',
                'PENSIUN ASABRI',
                'PRA PENSIUN ASN',
                'PRA PENSIUN DP TASPEN',
            ])->nullable();
            $table->double('lending_booking', 15, 2);
            $table->double('lending_pelunasan_pokok', 15, 2)->nullable();
            $table->double('lending_booking_bersih', 15, 2)->nullable();
            $table->date('lending_tanggal_realisasi')->nullable();
            $table->integer('lending_jkw')->nullable();
            $table->date('lending_tanggal_jatuh_tempo')->nullable();
            $table->date('lending_tanggal_rencana_bayar')->nullable();
            $table->date('lending_tanggal_rencana_takeover')->nullable();
            $table->double('lending_pot_provisi', 15, 2)->nullable();
            $table->double('lending_pot_admin', 15, 2)->nullable();
            $table->double('lending_pot_asuransi', 15, 2)->nullable();
            $table->double('lending_pot_asuransi_extra', 15, 2)->nullable();
            $table->double('lending_bunga_muka', 15, 2)->nullable();
            $table->double('lending_saldo_tab_mengendap', 15, 2)->nullable();
            $table->double('lending_angsuran_muka', 15, 2)->nullable();
            $table->double('lending_nominal_pelunasan_takeover', 15, 2)->nullable();
            $table->double('lending_booking_bersih2', 15, 2)->nullable();
            $table->string('lending_kre_rekening')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeksi_lendings');
    }
};
