<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdukFunding extends BaseModel
{
    protected $fillable = [
        'produk_jenis',
        'produk_nama',
    ];

    protected $casts = [
        'produk_jenis' => 'string',
        'produk_nama' => 'string',
    ];

    public function proyeksiFunding(): HasMany
    {
        return $this->hasMany(ProyeksiFunding::class, 'funding_produk', 'id');
    }
}
