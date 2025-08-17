<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProyeksiLendingProgressStatus extends Model
{
    protected $fillable = ['status'];

    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
