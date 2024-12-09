<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockEmail extends Model
{
    use HasFactory;
    protected $table = 'block_emails';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'email', 
        'remark',
        'created_at', 
        'updated_at'
    ];
}
