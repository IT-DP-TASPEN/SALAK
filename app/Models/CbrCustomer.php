<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbrCustomer extends Model
{
    protected $guarded = [];

    public function loanCollateralLists(): HasMany
    {
        return $this->hasMany(LoanCollateralList::class, 'cif_no', 'cif_no');
    }

    public function loanOutstandings(): HasMany
    {
        return $this->hasMany(LoanOutstanding::class, 'loan_cif', 'cif_no');
    }
}
