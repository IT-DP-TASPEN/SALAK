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
        Schema::table('cash_flow_kinds', function (Blueprint $table) {
            $table->integer('kind_sort_order')->default(0)->after('kind_pusat_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_flow_kinds', function (Blueprint $table) {
            $table->dropColumn('kind_sort_order');
        });
    }
};
