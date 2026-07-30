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
        Schema::table('cbr_customers', function (Blueprint $table) {
            $table->index(['fetch_date', 'owner_group', 'debtor_group'], 'cbr_customers_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbr_customers', function (Blueprint $table) {
            $table->dropIndex('cbr_customers_search_index');
        });
    }
};
