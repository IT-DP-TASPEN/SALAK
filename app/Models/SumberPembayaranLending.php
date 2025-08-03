<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumberPembayaranLending extends Model
{
    protected $fillable = ['sumber_nama'];

    public function proyeksiLendings(): HasMany
    {
        return $this->hasMany(ProyeksiLending::class, 'lending_sumber_pembayaran', 'id');
    }
}
