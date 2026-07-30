<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbrCustomer extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function loanOutstandings(): HasMany
    {
        return $this->hasMany(LoanOutstanding::class, 'loan_cif', 'cif_no');
    }
}
