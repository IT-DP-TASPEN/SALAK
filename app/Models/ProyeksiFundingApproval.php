<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyeksiFundingApproval extends BaseModel
{
    protected $fillable = [
        'approval_funding',
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

    public function proyeksiFunding(): BelongsTo
    {
        return $this->belongsTo(ProyeksiFunding::class, 'approval_funding', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_user', 'id');
    }
}
