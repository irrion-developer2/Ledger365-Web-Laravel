<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyVoucherItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tallyVoucher()
    {
        return $this->belongsTo(TallyVoucher::class, 'tally_voucher_id', 'id');
    }
    public function voucherHead()
    {
        return $this->belongsTo(TallyVoucher::class, 'tally_voucher_id');
    }
}
