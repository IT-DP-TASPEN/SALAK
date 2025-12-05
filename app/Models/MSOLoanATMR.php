<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MSOLoanATMR extends Model
{
    protected $guarded = [];
    protected $table = 'mso_loan_atmrs';

    public function loanOutstanding(): HasOne
    {
        return $this->hasOne(LoanOutstanding::class, 'loan_alt_account', 'loan_account');
    }
}
