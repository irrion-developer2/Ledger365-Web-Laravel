<?php

namespace App\Http\Controllers\App\Reports;

use Carbon\Carbon;
use App\Models\TallyCompany;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\TallyLedgerGroup;
use App\Models\TallyVoucherHead;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ReportBalanceSheetController extends Controller
{

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }


    public function index()
    {
        $companyIds = $this->reportService->companyData();

        $company = TallyCompany::where('company_id', $companyIds)->first();

        return view ('app.reports.balanceSheet.index', compact('company'));
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if (empty($companyIds)) {
            return DataTables::of([])->make(true);
        }

        if ($request->ajax()) {
            $startTime = microtime(true);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');
            $ledgerGroupHierarchy = ['Suspense A/c', 'Capital Account', 'Loans (Liability)', 'Current Liabilities', 'Fixed Assets', 'Investments', 'Current Assets'];
            $type = 'Group';

            $ledgerGroupHierarchy = (!empty($ledgerGroupHierarchy)) ? implode(',', $ledgerGroupHierarchy) : null;

            $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
            $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

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
                    case 'all':
                        break;
                }
            }

            $companyIdsList = implode(',', $companyIds);

            $sql = "CALL get_ledger_details_by_group(?, ?, ?, ?, ?)";

            Log::info("Calling Stored Procedure get_ledger_details_by_group", [
                'sql' => $sql,
                'params' => [
                    'p_company_ids' => $companyIdsList,
                    'p_start_date' => $startDate,
                    'p_end_date' => $endDate,
                    'p_ledger_group_hierarchy' => $ledgerGroupHierarchy,
                    'p_type' => $type
                ]
            ]);

            try {
                $dayBook = DB::select($sql, [
                    $companyIdsList,    
                    $startDate,         
                    $endDate,          
                    $ledgerGroupHierarchy,
                    $type,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_ledger_details_by_group:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }

            // dd($dayBook);
            
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first DB request execution time for ReportBalanceSheetController.getData:', [
                'time_taken' => $executionTime1 . ' seconds'
            ]);

            $dataTable = DataTables::of($dayBook)
                ->addIndexColumn()
                ->addColumn('opening_balance', function ($data) {
                    return indian_format($data->opening_balance);
                })
                ->addColumn('total_debit', function ($data) {
                    return indian_format($data->total_debit);
                })
                ->addColumn('total_credit', function ($data) {
                    return indian_format($data->total_credit);
                })
                ->addColumn('closing_balance', function ($data) {
                    return indian_format($data->closing_balance);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportBalanceSheetController.getData:', [
                'time_taken' => $executionTime . ' seconds'
            ]);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    // public function getData(Request $request)
    // {
    //     $companyIds = $this->reportService->companyData();

    //     if (empty($companyIds)) {
    //         return DataTables::of([])->make(true);
    //     }

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $startDate = $request->get('start_date');
    //         $endDate = $request->get('end_date');
    //         $customDateRange = $request->get('custom_date_range');
    //         $ledgerGroupName = ['Suspense A/c', 'Capital Account', 'Loans (Liability)', 'Current Liabilities', 'Fixed Assets', 'Investments', 'Current Assets'];

    //         $ledgerGroupName = (!empty($ledgerGroupName)) ? implode(',', $ledgerGroupName) : null;

    //         $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
    //         $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

    //         if ($customDateRange) {
    //             switch ($customDateRange) {
    //                 case 'this_month':
    //                     $startDate = now()->startOfMonth()->toDateString();
    //                     $endDate = now()->endOfMonth()->toDateString();
    //                     break;
    //                 case 'last_month':
    //                     $startDate = now()->subMonth()->startOfMonth()->toDateString();
    //                     $endDate = now()->subMonth()->endOfMonth()->toDateString();
    //                     break;
    //                 case 'this_quarter':
    //                     $startDate = now()->firstOfQuarter()->toDateString();
    //                     $endDate = now()->lastOfQuarter()->toDateString();
    //                     break;
    //                 case 'prev_quarter':
    //                     $startDate = now()->subQuarter()->firstOfQuarter()->toDateString();
    //                     $endDate = now()->subQuarter()->lastOfQuarter()->toDateString();
    //                     break;
    //                 case 'this_year':
    //                     $startDate = now()->startOfYear()->toDateString();
    //                     $endDate = now()->endOfYear()->toDateString();
    //                     break;
    //                 case 'prev_year':
    //                     $startDate = now()->subYear()->startOfYear()->toDateString();
    //                     $endDate = now()->subYear()->endOfYear()->toDateString();
    //                     break;
    //                 case 'all':
    //                     break;
    //             }
    //         }

    //         $companyIdsList = implode(',', $companyIds);

    //         $sql = "CALL get_balance_sheet_data(?, ?, ?, ?)";

    //         Log::info("Calling Stored Procedure get_balance_sheet_data", [
    //             'sql' => $sql,
    //             'params' => [
    //                 'company_ids' => $companyIdsList,
    //                 'start_date' => $startDate,
    //                 'end_date' => $endDate,
    //                 'ledger_group_name' => $ledgerGroupName
    //             ]
    //         ]);

    //         try {
    //             $dayBook = DB::select($sql, [
    //                 $companyIdsList,    
    //                 $startDate,         
    //                 $endDate,          
    //                 $ledgerGroupName
    //             ]);
    //         } catch (\Exception $e) {
    //             Log::error('Error executing stored procedure get_balance_sheet_data:', [
    //                 'error' => $e->getMessage(),
    //                 'file' => $e->getFile(),
    //                 'line' => $e->getLine(),
    //                 'trace' => $e->getTraceAsString(),
    //             ]);
    //             return response()->json(['error' => 'Failed to retrieve data.'], 500);
    //         }

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first DB request execution time for ReportBalanceSheetController.getData:', [
    //             'time_taken' => $executionTime1 . ' seconds'
    //         ]);

    //         $dataTable = DataTables::of($dayBook)
    //             ->addIndexColumn()
    //             ->addColumn('opening_balance', function ($data) {
    //                 return indian_format($data->opening_balance);
    //             })
    //             ->addColumn('total_debit', function ($data) {
    //                 return indian_format($data->total_debit);
    //             })
    //             ->addColumn('total_credit', function ($data) {
    //                 return indian_format($data->total_credit);
    //             })
    //             ->addColumn('closing_balance', function ($data) {
    //                 return indian_format($data->closing_balance);
    //             })
    //             ->make(true);

    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for ReportBalanceSheetController.getData:', [
    //             'time_taken' => $executionTime . ' seconds'
    //         ]);

    //         return $dataTable;
    //     }

    //     return response()->json(['message' => 'Invalid request.'], 400);
    // }

    public function LedgerDetails(Request $request)
    {
        $ledgerGroupHierarchy  = $request->input('ledger_group_hierarchy');

        return view('app.reports.balanceSheet._ledger_details', [
            'ledgerGroupHierarchy' => $ledgerGroupHierarchy,
        ]);
    }

    public function getLedgerDetails(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if (empty($companyIds)) {
            return DataTables::of([])->make(true);
        }

        if ($request->ajax()) {
            $startTime = microtime(true);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');
            $ledgerGroupHierarchy =  $request->get('ledger_group_hierarchy');
            $type = 'Ledger';


            $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
            $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

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
                    case 'all':
                        break;
                }
            }

            $companyIdsList = implode(',', $companyIds);

            $sql = "CALL get_ledger_details_by_group(?, ?, ?, ?, ?)";

            Log::info("Calling Stored Procedure get_ledger_details_by_group", [
                'sql' => $sql,
                'params' => [
                    'p_company_ids' => $companyIdsList,
                    'p_start_date' => $startDate,
                    'p_end_date' => $endDate,
                    'p_ledger_group_hierarchy' => $ledgerGroupHierarchy,
                    'p_type' => $type
                ]
            ]);

            try {
                $dayBook = DB::select($sql, [
                    $companyIdsList,    
                    $startDate,         
                    $endDate,          
                    $ledgerGroupHierarchy,
                    $type,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_ledger_details_by_group:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first DB request execution time for ReportBalanceSheetController.getLedgerDetails:', [
                'time_taken' => $executionTime1 . ' seconds'
            ]);

            $dataTable = DataTables::of($dayBook)
                ->addIndexColumn()
                ->addColumn('opening_balance', function ($data) {
                    return indian_format($data->opening_balance);
                })
                ->addColumn('total_debit', function ($data) {
                    return indian_format($data->total_debit);
                })
                ->addColumn('total_credit', function ($data) {
                    return indian_format($data->total_credit);
                })
                ->addColumn('closing_balance', function ($data) {
                    return indian_format($data->closing_balance);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportBalanceSheetController.getLedgerDetails:', [
                'time_taken' => $executionTime . ' seconds'
            ]);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }


}
