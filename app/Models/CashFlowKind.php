<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CashFlowKind extends BaseModel
{
    protected $fillable = [
        'kind_name',
        'kind_type',
        'kind_description',
        'kind_pusat_only',
    ];

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'cash_kind', 'id');
    }
}
