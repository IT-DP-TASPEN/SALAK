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
        Schema::table('saldo_neracas', function (Blueprint $table) {
            $table->unique(['tanggal', 'cabang', 'noakun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saldo_neracas', function (Blueprint $table) {
            $table->dropUnique(['tanggal', 'cabang', 'noakun']);
        });
    }
};
