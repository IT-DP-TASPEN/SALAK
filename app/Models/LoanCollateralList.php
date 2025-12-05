<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCollateralList extends Model
{
    protected $guarded = [];

    public function cbrCustomer(): BelongsTo
    {
        return $this->belongsTo(CbrCustomer::class, 'cif_no', 'cif_no');
    }
}
