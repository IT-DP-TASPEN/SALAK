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
        Schema::create('saldo_neracas', function (Blueprint $table) {
            $table->id();
            $table->string('cabang');
            $table->date('tanggal');
            $table->string('noakun');
            $table->string('namaakun');
            $table->decimal('saldoawal', 15, 2);
            $table->decimal('mutasidebit', 15, 2);
            $table->decimal('mutasikredit', 15, 2);
            $table->decimal('saldoakhir', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_neracas');
    }
};
