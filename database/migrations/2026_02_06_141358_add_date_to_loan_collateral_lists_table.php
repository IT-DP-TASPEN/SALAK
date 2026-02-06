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
        Schema::table('loan_collateral_lists', function (Blueprint $table) {
            $table
                ->date('fetch_date')
                ->nullable()
                ->after('branch_code');
            $table->index(['fetch_date', 'branch_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_collateral_lists', function (Blueprint $table) {
            $table->dropIndex(['fetch_date', 'branch_code']);
            $table->dropColumn('fetch_date');
        });
    }
};
