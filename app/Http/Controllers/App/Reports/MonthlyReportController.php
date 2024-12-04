<?php

namespace App\Http\Controllers\App\Reports;

use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class MonthlyReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function index()
    {
        return view ('app.reports.monthlyReport._sales');
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
            $voucherTypeName = 'Sales';
            $entryType = 'credit';

            $voucherTypeName = ($voucherTypeName && strtolower($voucherTypeName) !== 'null' && trim($voucherTypeName) !== '') ? $voucherTypeName : null;
            $entryType = ($entryType && strtolower($entryType) !== 'null' && trim($entryType) !== '') ? $entryType : null;
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

            $sql = "CALL get_MonthlyReport_data(?, ?, ?, ?, ?)";


            Log::info("Calling Stored Procedure get_MonthlyReport_data", [
                'sql' => $sql,
                'params' => [
                    'p_voucher_type_name' => $voucherTypeName,
                    'company_ids' => $companyIdsList,
                    'p_start_date' => $startDate,
                    'p_end_date' => $endDate,
                    'p_entry_types' => $entryType,
                ]
            ]);

            try {
                $monthlyReport = DB::select($sql, [ 
                    $voucherTypeName,
                    $companyIdsList,
                    $startDate,
                    $endDate,
                    $entryType,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_MonthlyReport_data:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first DB request execution time for MonthlyReportController.getData:', [
                'time_taken' => $executionTime1 . ' seconds'
            ]);

            $dataTable = DataTables::of($monthlyReport)
                ->addIndexColumn()
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for MonthlyReportController.getData:', [
                'time_taken' => $executionTime . ' seconds'
            ]);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    public function showMonthlySaleDetail($company_id, $year, $month)
    {
        if (!is_numeric($company_id) || !checkdate($month, 1, $year)) {
            return redirect()->back()->with('error', 'Invalid parameters provided.');
        }
    
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date("Y-m-t", strtotime($startDate));
    
        $voucherTypeName = 'Sales';
        $entryType = 'credit';
    
        $voucherTypeName = ($voucherTypeName && strtolower($voucherTypeName) !== 'null' && trim($voucherTypeName) !== '') ? $voucherTypeName : null;
        $entryType = ($entryType && strtolower($entryType) !== 'null' && trim($entryType) !== '') ? $entryType : null;
    
        $sql = "CALL get_MonthlyDetailReport_data(?, ?, ?, ?, ?)";
    
        Log::info("Calling Stored Procedure get_MonthlyDetailReport_data for detailed view", [
            'sql' => $sql,
            'params' => [
                'p_voucher_type_name' => $voucherTypeName,
                'company_ids' => $company_id,
                'p_start_date' => $startDate,
                'p_end_date' => $endDate,
                'p_entry_types' => $entryType,
            ]
        ]);
    
        try {
            $monthlyDetail = DB::select($sql, [ 
                $voucherTypeName,
                $company_id,
                $startDate,
                $endDate,
                $entryType,
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing stored procedure get_MonthlyDetailReport_data for detailed view:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to retrieve detailed data.');
        }

        $company = DB::table('tally_companies')->where('company_id', $company_id)->first();
    
        return view('app.reports.monthlyReport._monthly_details', [
            'monthlyDetail' => $monthlyDetail,
            'company' => $company,
            'year' => $year,
            'month' => $month,
            'voucherTypeName' => $voucherTypeName,
        ]);
    }
    
    public function showMonthlyPurchaseDetail($company_id, $year, $month)
    {
        if (!is_numeric($company_id) || !checkdate($month, 1, $year)) {
            return redirect()->back()->with('error', 'Invalid parameters provided.');
        }
    
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date("Y-m-t", strtotime($startDate));
    
        $voucherTypeName = 'Purchase';
        $entryType = 'debit';
    
        $voucherTypeName = ($voucherTypeName && strtolower($voucherTypeName) !== 'null' && trim($voucherTypeName) !== '') ? $voucherTypeName : null;
        $entryType = ($entryType && strtolower($entryType) !== 'null' && trim($entryType) !== '') ? $entryType : null;
    
        $sql = "CALL get_MonthlyDetailReport_data(?, ?, ?, ?, ?)";
    
        Log::info("Calling Stored Procedure get_MonthlyDetailReport_data for detailed view", [
            'sql' => $sql,
            'params' => [
                'p_voucher_type_name' => $voucherTypeName,
                'company_ids' => $company_id,
                'p_start_date' => $startDate,
                'p_end_date' => $endDate,
                'p_entry_types' => $entryType,
            ]
        ]);
    
        try {
            $monthlyDetail = DB::select($sql, [ 
                $voucherTypeName,
                $company_id,
                $startDate,
                $endDate,
                $entryType,
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing stored procedure get_MonthlyDetailReport_data for detailed view:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to retrieve detailed data.');
        }

        $company = DB::table('tally_companies')->where('company_id', $company_id)->first();
    
        return view('app.reports.monthlyReport._monthly_details', [
            'monthlyDetail' => $monthlyDetail,
            'company' => $company,
            'year' => $year,
            'month' => $month,
            'voucherTypeName' => $voucherTypeName,
        ]);
    }

    public function PurchaseIndex()
    {
        return view ('app.reports.monthlyReport._purchase');
    }

    public function getPurchaseData(Request $request)
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
            $voucherTypeName = 'Purchase';
            $entryType = 'debit';

            $voucherTypeName = ($voucherTypeName && strtolower($voucherTypeName) !== 'null' && trim($voucherTypeName) !== '') ? $voucherTypeName : null;
            $entryType = ($entryType && strtolower($entryType) !== 'null' && trim($entryType) !== '') ? $entryType : null;
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

            $sql = "CALL get_MonthlyReport_data(?, ?, ?, ?, ?)";


            Log::info("Calling Stored Procedure get_MonthlyReport_data", [
                'sql' => $sql,
                'params' => [
                    'p_voucher_type_name' => $voucherTypeName,
                    'company_ids' => $companyIdsList,
                    'p_start_date' => $startDate,
                    'p_end_date' => $endDate,
                    'p_entry_types' => $entryType,
                ]
            ]);

            try {
                $monthlyReport = DB::select($sql, [ 
                    $voucherTypeName,
                    $companyIdsList,
                    $startDate,
                    $endDate,
                    $entryType,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_MonthlyReport_data:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first DB request execution time for MonthlyReportController.getPurchaseData:', [
                'time_taken' => $executionTime1 . ' seconds'
            ]);

            $dataTable = DataTables::of($monthlyReport)
                ->addIndexColumn()
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for MonthlyReportController.getPurchaseData:', [
                'time_taken' => $executionTime . ' seconds'
            ]);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }
}
