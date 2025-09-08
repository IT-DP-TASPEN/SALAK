<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoNeraca extends Model
{
    protected $fillable = [
        'cabang',
        'tanggal',
        'noakun',
        'namaakun',
        'saldoawal',
        'mutasidebit',
        'mutasikredit',
        'saldoakhir',
    ];

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'cabang', 'id');
    }
}
