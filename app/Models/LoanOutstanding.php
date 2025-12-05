<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanOutstanding extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'loan_date_params',
        'loan_branch_office',
        'loan_product',
        'loan_account',
        'loan_customer',
        'loan_cif',
        'loan_alt_account',
        'loan_agreement_no',
        'loan_start_date',
        'loan_end_date',
        'loan_interest_rate',
        'loan_installment_loans',
        'loan_bi_collectability',
        'loan_days_past_due',
        'loan_currency',
        'loan_principal',
        'loan_outstanding',
        'loan_principal_arrears',
        'loan_interest_arrears',
        'loan_penalty_arrears',
        'loan_accrue_interest',
        'loan_marketing_code',
        'loan_over_repayment',
    ];

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'loan_branch_office', 'branch_code_fincloud');
    }

    public function cbrCustomer(): BelongsTo
    {
        return $this->belongsTo(CbrCustomer::class, 'loan_cif', 'cif_no');
    }

    public function msoLoanAtmr(): BelongsTo
    {
        return $this->belongsTo(MSOLoanATMR::class, 'loan_alt_account', 'loan_account');
    }
}
