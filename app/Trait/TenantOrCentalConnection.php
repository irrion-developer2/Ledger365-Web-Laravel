<?php

declare(strict_types=1);

namespace App\Trait;


trait TenantOrCentalConnection
{
    public function getConnectionName()
    {
        return is_null(tenant('id')) ? config('tenancy.database.central_connection')  : 'tenant';
    }
}
