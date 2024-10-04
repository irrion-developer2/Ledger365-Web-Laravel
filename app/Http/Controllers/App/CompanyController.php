<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class CompanyController extends Controller
{
    public function fetchCompanyData($companyId)
    {
        try {
            // Fetch the company data based on the ID
            $company = TallyCompany::select('id', 'guid', 'name', 'state')->findOrFail($companyId);

            // Return the company data in JSON format
            return response()->json([
                'company' => $company,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
