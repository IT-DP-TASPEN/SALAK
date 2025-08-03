<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProyeksiLending extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'lending_tanggal' => 'datetime',
        'lending_sumber_pembayaran' => 'string',
        'lending_status_dapem' => 'string',
        'lending_status_kerja' => 'string',
        'lending_produk' => 'string',
    ];

    // booted method to set default values for certain fields
    protected static function boot()
    {
        parent::boot();

        static::created(function ($lending) {
            $approval = new ProyeksiLendingApproval();
            $approval->approval_lending = $lending->id;
            $approval->approval_status = 'Pending';
            $approval->save();
        });
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'lending_kantor', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lending_agent', 'id');
    }

    public function approvals(): HasOne
    {
        return $this->hasOne(ProyeksiLendingApproval::class, 'approval_lending', 'id');
    }
}
