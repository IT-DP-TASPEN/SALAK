<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MitraBayar extends Model
{
    protected $fillable = [
        'mitra_nama',
    ];

    public function proyeksiLendings(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_mitra_bayar_takeover', 'id');
    }
}
