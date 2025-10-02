<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ProyeksiLendingProgressStatus extends Model
{
    protected $fillable = ['progress_status'];

    protected $casts = [
        'progress_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function proyeksiLendingProgress(): HasMany
    {
        return $this->hasMany(ProyeksiLendingProgress::class, 'progress_status');
    }

    public function proyeksiLending(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProyeksiLending::class,
            ProyeksiLendingProgress::class,
            'progress_status', // Foreign key on ProyeksiLendingProgress table
            'id', // Foreign key on ProyeksiLending table
            'id', // Local key on ProyeksiLendingProgressStatus table
            'progress_lending' // Local key on ProyeksiLending table
        );
    }
}
