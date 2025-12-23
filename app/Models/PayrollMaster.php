<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollMaster extends Model
{
    protected $connection = 'edapem';
    protected $table = 'payroll_dapem_masters';

    public function loanOutstandings(): HasMany
    {
        return $this->hasMany(LoanOutstanding::class, 'loan_cif', 'customer_id');
    }
}
