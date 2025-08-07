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
            $table->enum('lending_jenis_pengajuan', ['BARU', 'TOP UP']);
            $table->foreignId('lending_produk')
                ->nullable()
                ->constrained('produk_lendings')
                ->onDelete('set null');
            $table->foreignId('lending_sumber_pembayaran')
                ->nullable()
                ->constrained('sumber_pembayaran_lendings')
                ->onDelete('set null');
            $table->foreignId('lending_status_dapem')
                ->nullable()
                ->constrained('status_dapems')
                ->onDelete('set null');
            $table->foreignId('lending_status_kerja')
                ->nullable()
                ->constrained('status_kerjas')
                ->onDelete('set null');
            $table->double('lending_booking', 15, 2);
            $table->double('lending_pelunasan_pokok', 15, 2);
            $table->double('lending_booking_bersih', 15, 2);
            $table->date('lending_tanggal_realisasi');
            $table->integer('lending_jkw');
            $table->date('lending_tanggal_jatuh_tempo');
            $table->date('lending_tanggal_rencana_bayar');
            $table->date('lending_tanggal_rencana_takeover')->nullable();
            $table->double('lending_pot_provisi', 15, 2);
            $table->double('lending_pot_admin', 15, 2);
            $table->double('lending_pot_asuransi', 15, 2);
            $table->double('lending_pot_asuransi_extra', 15, 2);
            $table->double('lending_bunga_muka', 15, 2)->nullable();
            $table->double('lending_saldo_tab_mengendap', 15, 2);
            $table->double('lending_angsuran_muka', 15, 2);
            $table->double('lending_nominal_pelunasan_takeover', 15, 2)->nullable();
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
