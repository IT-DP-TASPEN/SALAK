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
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table->double('funding_nominal_bersih', 15, 2)->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyeksi_fundings', function (Blueprint $table) {
            $table->double('funding_nominal_bersih', 15, 2)->nullable(false)->change();
        });
    }
};
