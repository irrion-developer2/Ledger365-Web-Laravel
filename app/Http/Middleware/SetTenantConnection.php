<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class SetTenantConnection
{
    public function handle($request, Closure $next)
    {
        // Assuming you get the tenant ID from the request header or another source
        $tenantId = $request->header('Tenant-ID'); // Adjust as needed

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID not provided'], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        Log::info('Switching to tenant database: ' . $tenant->database_name);

        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::setDefaultConnection('tenant');

        return $next($request);
    }
}
