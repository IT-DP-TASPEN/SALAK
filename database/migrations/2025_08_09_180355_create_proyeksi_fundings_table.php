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
        Schema::create('proyeksi_fundings', function (Blueprint $table) {
            $table->id();
            $table->date('funding_tanggal');
            $table->foreignId('funding_kantor')
                ->constrained('branch_offices', 'id')
                ->cascadeOnDelete();
            $table->foreignId('funding_agent')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->foreignId('funding_produk')
                ->constrained('produk_fundings', 'id');
            $table->enum('funding_deposito_jenis', [
                'Baru',
                'Cair Tanam'
            ])
                ->nullable();
            $table->string('funding_nasabah_nama');
            $table->double('funding_nominal', 15, 2);
            $table->double('funding_nominal_bersih', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeksi_fundings');
    }
};
