<?php

namespace App\Http\Controllers\App;

use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyCompany;
use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.companies.index');
    }

    public function getData(Request $request)
    {

        $userId = auth()->user()->id;

        if (empty($userId)) {
            return DataTables::of([])->make(true);
        }

        if ($request->ajax()) {
            $startTime = microtime(true);

            $sql = "CALL GetUserCompaniesData(:p_user_id)";

            Log::info("Calling Stored Procedure", [
                'sql' => $sql,
                'params' => [
                    'p_user_id' => $userId,
                ]
            ]);

            try {
                $companies = DB::select($sql, [
                    'p_user_id' => $userId
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure GetUserCompaniesData:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }


            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CompanyController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($companies)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    return view('app.companies._action', compact('data'))->render();
                })               
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CompanyController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    public function deleteCompanies(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'company_ids' => 'required|string',
        ]);

        $companyIds = $request->input('company_ids');

        $idsArray = explode(',', $companyIds);
        foreach ($idsArray as $id) {
            if (!is_numeric($id)) {
                return response()->json(['success' => false, 'message' => 'Invalid company ID provided.'], 400);
            }
        }

        $uniqueIds = implode(',', array_unique($idsArray));

        $deletedCompanies = [];
        $notFoundCompanies = [];

        foreach ($idsArray as $id) {
            $company = TallyCompany::find($id);
            if ($company) {
                $deletedCompanies[] = $id;
            } else {
                $notFoundCompanies[] = $id;
            }
        }

        Log::info("Deleting Companies", [
            'user_id' => $user->id,
            'company_ids' => $uniqueIds,
            'deleted_companies' => $deletedCompanies,
            'not_found_companies' => $notFoundCompanies,
        ]);

        if (!empty($deletedCompanies)) {
            try {
                $procedure = "CALL DeleteMultipleCompaniesData(:p_company_ids)";
                DB::statement($procedure, ['p_company_ids' => implode(',', $deletedCompanies)]);

                Log::info("Successfully deleted companies.", [
                    'user_id' => $user->id,
                    'company_ids' => implode(',', $deletedCompanies),
                ]);

                $message = count($deletedCompanies) . " company(s) deleted successfully.";
                if (!empty($notFoundCompanies)) {
                    $message .= " " . count($notFoundCompanies) . " company(s) not found.";
                }

                return response()->json(['success' => true, 'message' => $message]);
            } catch (\Exception $e) {
                Log::error("Error deleting companies.", [
                    'user_id' => $user->id,
                    'company_ids' => implode(',', $deletedCompanies),
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['success' => false, 'message' => 'Failed to delete companies.'], 500);
            }
        } else {
            $message = "No companies found to delete.";
            if (!empty($notFoundCompanies)) {
                $message = count($notFoundCompanies) . " company(s) not found.";
            }
            return response()->json(['success' => false, 'message' => $message], 404);
        }
    }


    public function fetchCompanyData($companyId)
    {
        try {
            $company = TallyCompany::select('id', 'guid', 'name', 'state')->findOrFail($companyId);

            return response()->json([
                'company' => $company,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
