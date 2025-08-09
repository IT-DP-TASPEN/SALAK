<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
