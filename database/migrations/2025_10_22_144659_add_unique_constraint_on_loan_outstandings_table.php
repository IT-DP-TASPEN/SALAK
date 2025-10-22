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
        Schema::table('loan_outstandings', function (Blueprint $table) {
            $table->unique(
                ['loan_date_params', 'loan_branch_office', 'loan_account'],
                'uniq_lou_date_branch_account'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_outstandings', function (Blueprint $table) {
            $table->dropUnique('uniq_lou_date_branch_account');
        });
    }
};
