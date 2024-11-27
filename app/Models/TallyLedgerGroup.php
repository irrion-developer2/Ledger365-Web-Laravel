<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyLedgerGroup extends Model
{
    use HasFactory;
    protected $primaryKey = 'ledger_group_id';
    protected $guarded = [];

    public function tallyLedgers()
    {
        return $this->hasMany(TallyLedger::class, 'parent', 'name');
    }

    public function ledgers()
    {
        return $this->hasMany(TallyLedger::class, 'ledger_group_id', 'ledger_group_id');
    }
}
