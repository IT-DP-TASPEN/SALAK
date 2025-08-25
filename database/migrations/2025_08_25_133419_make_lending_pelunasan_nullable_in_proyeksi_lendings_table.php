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
            $table->double('lending_pelunasan_pokok', 15, 2)->nullable()->change();
            $table->double('lending_pelunasan_bunga', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_lendings', function (Blueprint $table) {
            $table->double('lending_pelunasan_pokok', 15, 2)->nullable(false)->change();
            $table->double('lending_pelunasan_bunga', 15, 2)->nullable(false)->change();
        });
    }
};
