<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($cashflow) {
            $approval = new CashFlowApproval();
            $approval->approval_cash_flow = $cashflow->id;
            $approval->approval_status = 'Pending';
            $approval->save();
        });
    }

    public function approval(): HasOne
    {
        return $this->hasOne(CashFlowApproval::class, 'approval_cash_flow', 'id');
    }

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
