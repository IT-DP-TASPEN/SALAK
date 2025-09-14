<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowApproval extends BaseModel
{
    protected $fillable = [
        'approval_cash_flow',
        'approval_user',
        'approval_status',
        'approval_comment',
        'approval_approved_at',
        'approval_rejected_at',
    ];

    protected $casts = [
        'approval_status' => 'string',
        'approval_comment' => 'string',
        'approval_approved_at' => 'datetime',
        'approval_rejected_at' => 'datetime',
    ];

    public function cashFlow(): BelongsTo
    {
        return $this->belongsTo(CashFlow::class, 'approval_cash_flow', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_user', 'id');
    }
}
