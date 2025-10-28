<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BOSSAPPFLAG extends Model
{
    protected $connection = 'boss';
    protected $table = 'dbo.APPFLAG';
    protected $primaryKey = 'AP_REGNO';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [];

    public function bossApp(): BelongsTo
    {
        return $this->belongsTo(BOSSAPP::class, 'AP_REGNO', 'AP_REGNO');
    }

    public function proyeksiLending(): HasOne
    {
        return $this->hasOne(ProyeksiLending::class, 'lending_boss_application_number', 'AP_REGNO');
    }
}
