<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $fillable = [
        'jabatan_nama',
        'jabatan_target',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'agent_jabatan', 'id');
    }
}
