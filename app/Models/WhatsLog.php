<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsLog extends Model
{
    use HasFactory;
    protected $table = 'whatsapp_logs';
    protected $primaryKey = 'whatsapp_id';
    protected $fillable = [
        'company_id', 
        'ledger_id', 
        'phone_number', 
        'message', 
        'pdf_path', 
        'json_response', 
        'created_at', 
        'updated_at'
    ];
}
