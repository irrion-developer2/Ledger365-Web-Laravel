<?php

namespace App\Models;

use App\Trait\TenantOrCentalConnection;
use Laravel\Jetstream\Membership as JetstreamMembership;

class Membership extends JetstreamMembership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    use TenantOrCentalConnection;
    public $incrementing = true;
}
