<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataAbaMaster extends Model
{
    protected $connection = 'mso';
    protected $table = 'data_aba_master';
    protected $primaryKey = 'aba_kode';
    public $incrementing = false;
    protected $keyType = 'string';

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'aba_kantor', 'branch_code');
    }

    public function abaTrans(): HasMany
    {
        return $this->hasMany(DataAbaTrans::class, 'trans_rekening', 'aba_kode');
    }
}
