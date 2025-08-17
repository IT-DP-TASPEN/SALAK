<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyeksiLendingProgress extends Model
{
    protected $fillable = [
        'progress_lending',
        'progress_status'
    ];

    public function proyeksiLending(): BelongsTo
    {
        return $this->belongsTo(ProyeksiLending::class, 'progress_lending');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProyeksiLendingProgressStatus::class, 'progress_status');
    }
}
