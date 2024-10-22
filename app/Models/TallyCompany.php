<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyCompany extends Model
{
    use HasFactory;
    protected $primaryKey = 'company_id';
    protected $guarded = [];
}
