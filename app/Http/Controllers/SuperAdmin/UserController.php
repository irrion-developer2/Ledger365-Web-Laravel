<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyCompany;
use App\Models\TallyLedgerGroup;
use App\Models\TallyItem;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyGodown;
use App\Models\TallyUnit;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Yajra\DataTables\Facades\DataTables;
use App\DataTables\SuperAdmin\UserDataTable;

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

    public function show($user)
    {
        $users = User::where('id', $user)->firstOrFail();
        return view('superadmin.users._user_details', compact('users'));
    }

    public function getData(Request $request, $users)
    {
        if ($request->ajax()) {

            $user = User::find($users);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            $subIds = json_decode($user->sub_id);
            if (is_array($subIds)) {
                $companies = TallyCompany::whereIn('sub_id', $subIds)->get();
            } elseif (!is_null($subIds)) {
                $companies = TallyCompany::where('sub_id', $user->sub_id)->get();
            } else {
                $companies = TallyCompany::where('sub_id', $user->sub_id)->get();
            }

            $dataTable = DataTables::of($companies)
                ->addIndexColumn()
                ->make(true);

                return $dataTable;
        }
    }


    public function deleteCompany(Request $request)
    {
        $guid = $request->input('guid');

        $company = TallyCompany::where('guid', $guid)->first();

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        TallyLedgerGroup::where('company_guid', $company->guid)->delete();
        TallyItem::where('company_guid', $company->guid)->delete();
        TallyLedger::where('company_guid', $company->guid)->delete();
        TallyVoucher::where('company_guid', $company->guid)->delete();
        TallyGodown::where('guid', 'LIKE', $company->guid . '%')->delete();
        TallyUnit::where('guid', 'LIKE', $company->guid . '%')->delete();

        $company->delete();

        return response()->json(['success' => 'Company and related data deleted successfully']);
    }

}
