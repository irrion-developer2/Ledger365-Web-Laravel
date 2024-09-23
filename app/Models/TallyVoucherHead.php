<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TallyVoucher;

class TallyVoucherHead extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function voucherHead()
    {
        return $this->belongsTo(TallyVoucher::class, 'tally_voucher_id');
    }

    public function tallyVoucher()
    {
        return $this->belongsTo(TallyVoucher::class, 'tally_voucher_id', 'id'); // Adjust as needed
    }

    public function voucher()
    {
        return $this->belongsTo(TallyVoucher::class, 'ledger_guid', 'ledger_guid');
    }

    public function ledger()
    {
        return $this->belongsTo(TallyLedger::class, 'ledger_guid', 'guid');
    }
}
