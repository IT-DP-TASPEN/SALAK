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
        Schema::table('produk_fundings', function (Blueprint $table) {
            $table->dropColumn('produk_jenis');
            $table->foreignId('produk_jenis')
                ->constrained('jenis_fundings')
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk_fundings', function (Blueprint $table) {
            $table->dropForeign(['produk_jenis']);
            $table->dropColumn('produk_jenis');
            $table->enum('produk_jenis', [
                'Deposito',
                'Tabungan',
            ])->after('id');
        });
    }
};
