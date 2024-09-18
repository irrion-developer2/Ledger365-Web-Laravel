<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tallyLedgers()
    {
        return $this->hasMany(TallyLedger::class, 'parent', 'name');
    }
}
