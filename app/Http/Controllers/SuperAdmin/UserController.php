<?php

namespace App\Http\Controllers\SuperAdmin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyItem;
use App\Models\TallyUnit;
use App\Models\TallyGodown;
use App\Models\TallyLedger;
use App\Models\TallyCompany;
use App\Models\TallyVoucher;
use Illuminate\Http\Request;
use App\Models\TallyLedgerGroup;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 
use Yajra\DataTables\Facades\DataTables;
use App\DataTables\SuperAdmin\UserDataTable;
use App\Models\UserCompanyMapping;

class UserController extends Controller
{

    public function index(UserDataTable $dataTable)
    {
        return $dataTable->render('superadmin.users.index');
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ]);

        $user = User::find($request->user_id);
        $user->status = $request->status;
 
        $user->save();

        return response()->json(['success' => true]);
    
    }


    public function getData(Request $request, $users)
    {

        $userId = User::find($users)->id;

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
            Log::info('Total first db request execution time for UserController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($companies)
                ->addIndexColumn()     
                ->addColumn('action', function ($data) {
                    return view('superadmin.users._action', compact('data'))->render();
                })        
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for UserController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

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

    public function companiesMapping(User $user)
    {
        return view('superadmin.users._company_mapping', ['userMapping' => $user]);
    }

    public function companiesMappingGetData(Request $request, $users)
    {
        $user = User::find($users);

        if (!$user) {
            return DataTables::of([])->make(true);
        }

        if ($request->ajax()) {
            $startTime = microtime(true);

            $sql = "
                SELECT 
                    tc.company_id,
                    tc.company_guid,
                    tc.alter_id,
                    tc.company_name,
                    tc.state,
                    tc.license_number,
                    CASE 
                        WHEN ucm.user_id IS NULL THEN 0 
                        ELSE 1 
                    END AS mapped
                FROM 
                    tally_companies tc
                LEFT JOIN 
                    user_company_mappings ucm 
                ON 
                    tc.company_id = ucm.company_id AND ucm.user_id = :user_id
            ";

            Log::info("Company Mapping Query", ['sql' => $sql, 'user_id' => $user->id]);

            $companies = DB::select(DB::raw($sql), ['user_id' => $user->id]);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('First DB request execution time for companiesMappingGetData:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($companies)
                ->addIndexColumn()
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total execution time for companiesMappingGetData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    public function updateCompanyMapping(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'company_ids' => 'array',
            'company_ids.*' => 'integer|exists:tally_companies,company_id',
        ]);

        $companyIds = $validated['company_ids'] ?? [];

        DB::transaction(function() use ($userId, $companyIds) {
            $currentCompanyIds = UserCompanyMapping::where('user_id', $userId)
                                                   ->pluck('company_id')
                                                   ->toArray();

            $companyIdsToAdd = array_diff($companyIds, $currentCompanyIds);
            $companyIdsToRemove = array_diff($currentCompanyIds, $companyIds);

            foreach ($companyIdsToAdd as $companyId) {
                UserCompanyMapping::create([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ]);
            }

            if(!empty($companyIdsToRemove)){
                UserCompanyMapping::where('user_id', $userId)
                                  ->whereIn('company_id', $companyIdsToRemove)
                                  ->delete();
            }
        });

        return response()->json(['success' => true, 'message' => 'Company mappings updated successfully.']);
    }
}
