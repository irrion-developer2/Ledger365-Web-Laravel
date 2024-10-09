<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{

    public function index()
    {
        return view ('app.employees.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $startTime = microtime(true);

            $employees = User::where('owner_employee_id', auth()->user()->id)
                                ->where('role', 'Employee')
                                ->get();

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;

            Log::info('Total first db request execution time for EmployeeController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $customDateRange = $request->get('custom_date_range');

            if ($customDateRange) {
                switch ($customDateRange) {
                    case 'this_month':
                        $startDate = now()->startOfMonth()->toDateString();
                        $endDate = now()->endOfMonth()->toDateString();
                        break;
                    case 'last_month':
                        $startDate = now()->subMonth()->startOfMonth()->toDateString();
                        $endDate = now()->subMonth()->endOfMonth()->toDateString();
                        break;
                    case 'this_quarter':
                        $startDate = now()->firstOfQuarter()->toDateString();
                        $endDate = now()->lastOfQuarter()->toDateString();
                        break;
                    case 'prev_quarter':
                        $startDate = now()->subQuarter()->firstOfQuarter()->toDateString();
                        $endDate = now()->subQuarter()->lastOfQuarter()->toDateString();
                        break;
                    case 'this_year':
                        $startDate = now()->startOfYear()->toDateString();
                        $endDate = now()->endOfYear()->toDateString();
                        break;
                    case 'prev_year':
                        $startDate = now()->subYear()->startOfYear()->toDateString();
                        $endDate = now()->subYear()->endOfYear()->toDateString();
                        break;
                }
            }

            if ($startDate && $endDate) {
                $employees->whereBetween('created_at', [$startDate, $endDate]);
            }

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            $dataTable = DataTables::of($employees)
                ->addIndexColumn()
                ->addColumn('status', function ($user) {
                    return view('app.employees._action', compact('user'))->render();
                })
                ->make(true);

                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                Log::info('Total end execution time for EmployeeController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

                return $dataTable;
        }
    }

    public function add()
    {
        return view('app.employees._add');
    }

    public function saveEmployee(Request $request)
    {
        $request->validate([
            'owner_employee_id' => 'required|exists:users,id',
        ]);

        $employee = new User();
        $employee->owner_employee_id = $request->input('owner_employee_id');
        $employee->role = $request->input('role');
        $employee->name = $request->input('name');
        $employee->email = $request->input('email');
        $employee->phone = $request->input('phone');
        $employee->tally_connector_id = $request->input('tally_connector_id');
        $employee->save();

        return redirect()->back()->with('success', 'Employee saved successfully!');
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

    public function getCompanyData(Request $request, $users)
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

        TallyGroup::where('company_guid', $company->guid)->delete();
        TallyItem::where('company_guid', $company->guid)->delete();
        TallyLedger::where('company_guid', $company->guid)->delete();
        TallyVoucher::where('company_guid', $company->guid)->delete();
        TallyGodown::where('guid', 'LIKE', $company->guid . '%')->delete();
        TallyUnit::where('guid', 'LIKE', $company->guid . '%')->delete();

        $company->delete();

        return response()->json(['success' => 'Company and related data deleted successfully']);
    }

}
