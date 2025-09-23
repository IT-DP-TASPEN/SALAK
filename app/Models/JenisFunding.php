<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisFunding extends Model
{
    protected $fillable = ['jenis_funding_nama'];

    public function produkFundings(): HasMany
    {
        return $this->hasMany(ProdukFunding::class, 'produk_jenis', 'id');
    }
}
