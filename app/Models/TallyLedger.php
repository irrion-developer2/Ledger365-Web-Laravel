<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TallyVoucherHead;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TallyLedger extends Model
{
    use HasFactory;
    protected $primaryKey = 'ledger_id';
    protected $guarded = [];

    public function ledgerGroup()
    {
        return $this->belongsTo(TallyLedgerGroup::class, 'ledger_group_id', 'ledger_group_id');
    }

    public function tallyVoucherHead()
    {
        return $this->belongsTo(TallyVoucherHead::class, 'ledger_guid', 'guid'); // 'guid' in TallyLedger and 'ledger_guid' in TallyVoucherHead
    }

    public function tallyVoucherHeads()
    {
        return $this->hasMany(TallyVoucherHead::class, 'ledger_guid', 'guid');
    }

    public function parentGroup()
    {
        return $this->belongsTo(TallyLedgerGroup::class, 'parent', 'name');
    }

    public function tallyVouchers()
    {
        return $this->hasMany(TallyVoucher::class, 'party_ledger_name', 'name');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(TallyVoucher::class, 'ledger_guid', 'guid');
    }

    public function vouchersHeads()
    {
        return $this->hasMany(TallyVoucherHead::class, 'ledger_guid', 'id');
    }

}
