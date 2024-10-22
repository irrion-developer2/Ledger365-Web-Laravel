<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyUnit extends Model
{
    use HasFactory;
    protected $primaryKey = 'unit_id';
    protected $guarded = [];
}
