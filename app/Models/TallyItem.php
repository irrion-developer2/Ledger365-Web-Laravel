<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_id';
    protected $guarded = [];

    public function tallyVoucherItems()
    {
        return $this->hasMany(TallyVoucherItem::class, 'stock_item_name', 'name');
    }
}
