<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAbaTrans extends Model
{
    protected $connection = 'mso';
    protected $table = 'data_aba_trans';
    protected $primaryKey = 'trans_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'trans_kantor', 'branch_code');
    }

    public function abaMaster(): BelongsTo
    {
        return $this->belongsTo(DataAbaMaster::class, 'trans_rekening', 'aba_kode');
    }
}
