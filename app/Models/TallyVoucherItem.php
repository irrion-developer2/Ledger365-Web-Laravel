<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyVoucherItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'voucher_item_id';
    protected $guarded = [];

    public function tallyVoucher()
{
    return $this->belongsTo(TallyVoucher::class, 'voucher_head_id', 'voucher_id'); // Update with correct foreign and local keys
}
    public function voucherHead()
    {
        return $this->belongsTo(TallyVoucher::class, 'tally_voucher_id');
    }
}
