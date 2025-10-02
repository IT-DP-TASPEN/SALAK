<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'agent_nama',
        'agent_mso_code',
        'agent_jabatan',
        'agent_branch_office',
    ];

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'agent_jabatan', 'id');
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'agent_branch_office', 'id');
    }

    public function proyeksiLending(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_agent', 'id');
    }

    public function proyeksiFunding(): HasMany
    {
        return $this->hasMany(ProyeksiFunding::class, 'funding_agent', 'id');
    }
}
