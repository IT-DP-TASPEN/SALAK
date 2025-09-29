<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerusahaanAsuransi extends Model
{
    protected $fillable = [
        'asuransi_npwp',
        'asuransi_nama',
        'asuransi_alamat',
        'asuransi_telepon',
    ];

    public function proyeksiLendings(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_asuransi_perusahaan', 'id');
    }
}
