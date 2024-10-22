<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyBillAllocation extends Model
{
    use HasFactory;
    protected $primaryKey = 'bill_allocation_id';
    protected $guarded = [];
}
