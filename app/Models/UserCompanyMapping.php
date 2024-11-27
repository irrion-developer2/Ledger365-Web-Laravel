<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCompanyMapping extends Model
{
    use HasFactory;
    protected $primaryKey = 'user_company_mapping_id';
    protected $guarded = [];
}
