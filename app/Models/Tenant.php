<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains,CentralConnection;

    // protected $connection = 'main'; // Adjust if needed

    public const CONNECTION = 'tenant';

    
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'password',
            'remember_token',
        ];
    }

    public function setPasswordAttribute($value){
        return $this->attributes['password'] = bcrypt($value);
    }
    
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    

}
