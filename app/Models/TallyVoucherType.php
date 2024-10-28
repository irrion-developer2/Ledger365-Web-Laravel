<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyVoucherType extends Model
{
    use HasFactory;
    protected $primaryKey = 'voucher_type_id';
    protected $guarded = [];
}
