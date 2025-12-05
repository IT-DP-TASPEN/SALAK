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
        Schema::create('loan_collateral_lists', function (Blueprint $table) {
            $table->id();
            $table->char('branch_code', 3);
            $table->string('product_name');
            $table->string('credit_limit_no');
            $table->string('cif_no');
            $table->string('customer_name');
            $table->string('currency');
            $table->decimal('credit_limit', 15, 2);
            $table->string('period');
            $table->string('loan_acc_no');
            $table->decimal('loan_principal', 15, 2);
            $table->decimal('outstanding', 15, 2);
            $table->string('collateral_type');
            $table->string('collateral_data');
            $table->string('collateral_no');
            $table->string('collateral_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->decimal('collateral_real_value', 15, 2)->nullable();
            $table->decimal('collateral_market_value', 15, 2)->nullable();
            $table->date('collateral_appraisal_date')->nullable();
            $table->string('officer_create');
            $table->date('input_date');

            $table
                ->foreign('branch_code')
                ->references('branch_code_fincloud')
                ->on('branch_offices')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_collateral_lists');
    }
};
