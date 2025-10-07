<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProyeksiFunding extends BaseModel
{
    protected $fillable = [
        'funding_tanggal',
        'funding_kantor',
        'funding_agent',
        'funding_petugas',
        'funding_produk',
        'funding_deposito_jenis',
        'funding_nasabah_nama',
        'funding_nominal',
        'funding_nominal_bersih',
    ];

    protected $casts = [
        'funding_tanggal' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($funding) {
            $approval = new ProyeksiFundingApproval();
            $approval->approval_funding = $funding->id;
            $approval->approval_status = 'Pending';
            $approval->save();
        });

        static::updating(function ($funding) {
            $approval = $funding->approval;
            if ($approval) {
                $approval->approval_status = 'Pending';
                $approval->approval_comment = null;
                $approval->save();
            }
        });
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'funding_kantor', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'funding_agent', 'id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'funding_petugas', 'id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(ProdukFunding::class, 'funding_produk', 'id');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(ProyeksiFundingApproval::class, 'approval_funding', 'id');
    }
}
