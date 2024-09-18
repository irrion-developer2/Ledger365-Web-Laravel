<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantHomeController extends Controller
{
    public function index()
    {
        $tenant = Tenant::count();
        return view('tenantdashboard', compact('tenant'));
        
    }
}
