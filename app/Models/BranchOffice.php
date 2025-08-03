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
}
