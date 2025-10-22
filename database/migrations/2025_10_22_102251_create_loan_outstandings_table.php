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
        Schema::create('loan_outstandings', function (Blueprint $table) {
            $table->id();
            $table->date('loan_date_params');
            $table->char('loan_branch_office', 3);
            $table->string('loan_product');
            $table->string('loan_account');
            $table->string('loan_customer');
            $table->string('loan_cif');
            $table->string('loan_alt_account')->nullable();
            $table->string('loan_agreement_no');
            $table->date('loan_start_date');
            $table->date('loan_end_date');
            $table->decimal('loan_interest_rate', 5, 2);
            $table->decimal('loan_installment_loans', 15, 2);
            $table
                ->integer('loan_bi_collectability')
                ->comment('1: Lancar, 2: Dalam Perhatian Khusus, 3: Kurang Lancar, 4: Diragukan, 5: Macet');
            $table->integer('loan_days_past_due');
            $table->string('loan_currency');
            $table->decimal('loan_principal', 15, 2);
            $table->decimal('loan_outstanding', 15, 2);
            $table->decimal('loan_principal_arrears', 15, 2);
            $table->decimal('loan_interest_arrears', 15, 2);
            $table->decimal('loan_penalty_arrears', 15, 2);
            $table->decimal('loan_accrue_interest', 15, 2);
            $table->string('loan_marketing_code')->nullable();
            $table->decimal('loan_over_repayment', 15, 2);

            $table->foreign('loan_branch_office')
                ->references('branch_code_fincloud')
                ->on('branch_offices')
                ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_outstandings');
    }
};
