<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdukFunding extends BaseModel
{
    protected $fillable = [
        'produk_jenis',
        'produk_nama',
    ];

    protected $casts = [
        'produk_nama' => 'string',
    ];

    public function proyeksiFunding(): HasMany
    {
        return $this->hasMany(ProyeksiFunding::class, 'funding_produk', 'id');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisFunding::class, 'produk_jenis', 'id');
    }
}
