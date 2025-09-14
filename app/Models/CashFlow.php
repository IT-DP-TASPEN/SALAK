<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    protected $fillable = [
        'cash_kind',
        'cash_kantor',
        'cash_user',
        'cash_tanggal',
        'cash_keterangan',
        'cash_jumlah',
    ];

    public function kind(): BelongsTo
    {
        return $this->belongsTo(CashFlowKind::class, 'cash_kind', 'id');
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'cash_kantor', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_user', 'id');
    }
}
