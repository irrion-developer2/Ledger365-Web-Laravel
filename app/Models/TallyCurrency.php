<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyCurrency extends Model
{
    use HasFactory;
    protected $primaryKey = 'currency_id';
    protected $guarded = [];
}
