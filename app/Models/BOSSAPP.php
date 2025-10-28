<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BOSSAPP extends Model
{
    protected $connection = 'boss';
    protected $table = 'dbo.APP';
    protected $primaryKey = 'AP_REGNO';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [];

    public function proyeksiLending(): HasOne
    {
        return $this->hasOne(ProyeksiLending::class, 'lending_boss_application_number', 'AP_REGNO');
    }

    public function flag(): HasOne
    {
        return $this->hasOne(BOSSAPPFLAG::class, 'AP_REGNO', 'AP_REGNO');
    }
}
