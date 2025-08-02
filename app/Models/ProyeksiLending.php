<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProyeksiLending extends Model
{
    protected $guarded = [];

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'lending_kantor', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lending_agent', 'id');
    }

    public static function getLendingTypes()
    {
        $type = DB::select(DB::raw('SHOW COLUMNS FROM proyeksi_lendings WHERE Field = "type"'))[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $values = array();
        foreach (explode(',', $matches[1]) as $value) {
            $values[] = trim($value, "'");
        }
        return $values;
    }
}
