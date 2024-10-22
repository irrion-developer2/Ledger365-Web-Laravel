<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyItemGroup extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_group_id';
    protected $guarded = [];
}
