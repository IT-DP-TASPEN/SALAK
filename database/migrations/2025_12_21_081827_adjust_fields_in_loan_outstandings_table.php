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
            $table->string('loan_cif_alt')->after('loan_cif')->nullable();
            $table->string('loan_credit_limit_no')->after('loan_alt_account')->nullable();
            $table->decimal('loan_installment_principal', 15, 2)->after('loan_installment_loans')->default(0);
            $table->decimal('loan_installment_interest', 15, 2)->after('loan_installment_principal')->default(0);
            // make loan_over_repayment default to 0
            $table->decimal('loan_over_repayment', 15, 2)->default(0)->change();
            $table->string('loan_agreement_no')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_outstandings', function (Blueprint $table) {
            $table->dropColumn('loan_cif_alt');
            $table->dropColumn('loan_credit_limit_no');
            $table->dropColumn('loan_installment_principal');
            $table->dropColumn('loan_installment_interest');
            $table->decimal('loan_over_repayment', 15, 2)->default(null)->change();
            $table->string('loan_agreement_no')->nullable(false)->change();
        });
    }
};
