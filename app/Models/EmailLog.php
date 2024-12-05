<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;
    protected $table = 'email_logs';
    protected $primaryKey = 'email_id';
    protected $fillable = [
        'company_id', 
        'ledger_id', 
        'email', 
        'message', 
        'pdf_path', 
        'json_response', 
        'created_at', 
        'updated_at'
    ];
}
