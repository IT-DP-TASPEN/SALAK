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

    public static function getPossibleEnumValues($name)
    {
        $instance = new static; // create an instance of the model to be able to get the table name
        $type = DB::select('SHOW COLUMNS FROM ' . $instance->getTable() . ' WHERE Field = "' . $name . '"')[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $enum = array();
        foreach (explode(',', $matches[1]) as $value) {
            $v = trim($value, "'");
            $enum[] = $v;
        }
        return $enum;
    }
}
