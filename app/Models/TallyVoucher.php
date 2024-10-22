<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyVoucher extends Model
{
    use HasFactory;
    protected $primaryKey = 'voucher_id';
    protected $guarded = [];

    public function voucherHead()
    {
        return $this->belongsTo(TallyVoucherHead::class, 'party_ledger_name', 'ledger_name');
    }

    public function voucherHeads()
    {
        return $this->hasMany(TallyVoucherHead::class, 'ledger_guid', 'ledger_guid');
    }

    public function tallyVoucherItems()
    {
        return $this->hasMany(TallyVoucherItem::class, 'tally_voucher_id');
    }

    public function voucher()
    {
        return $this->belongsTo(TallyVoucher::class, 'ledger_guid', 'ledger_guid');
    }

    public function ledger()
    {
        return $this->belongsTo(TallyLedger::class, 'ledger_guid', 'guid');
    }


    public function tallyVoucherHeadCustomer()
    {
        return $this->hasOne(TallyVoucherHead::class, 'tally_voucher_id', 'id');
    }

}
