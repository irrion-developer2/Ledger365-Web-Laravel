<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyStockItem extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'gst_details' => 'array',
        'hsn_details' => 'array',
        'batch_allocations' => 'array',
    ];
}
