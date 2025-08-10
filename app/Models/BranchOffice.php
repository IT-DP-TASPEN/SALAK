<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchOffice extends BaseModel
{
    protected $guarded = [];

    public function proyeksiLendings(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_kantor', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_office_id', 'id');
    }

    public function penempatanABA(): HasMany
    {
        return $this->hasMany(DataAbaMaster::class, 'aba_kantor', 'branch_code');
    }

    public function getSaldoAbaAttribute(): float
    {
        return $this->penempatanABA()
            ->sum('aba_saldo_efektif');
    }
}
