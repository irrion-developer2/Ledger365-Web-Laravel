<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 

class ReportOptionalController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.reports.optional.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');

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

            $startDateFilter = $startDate ? "'{$startDate}'" : 'NULL';
            $endDateFilter = $endDate ? "'{$endDate}'" : 'NULL';
    
            $companyIdsList = implode(',', $companyIds);

            $sql = "
                    SELECT
                        v.voucher_date,
                        GROUP_CONCAT(l.ledger_name SEPARATOR ', ') AS `ledger_name`,
                        c.company_name,
                        vt.voucher_type_name,
                        v.voucher_number,
                        SUM(CASE 
                                WHEN vh.entry_type = 'debit' THEN ABS(vh.amount) 
                                ELSE 0 
                            END) AS `total_debit`,
                        SUM(CASE 
                                WHEN vh.entry_type = 'credit' THEN vh.amount 
                                ELSE 0 
                            END) AS `total_credit`
                    FROM
                        tally_vouchers v
                    JOIN
                        tally_companies c ON v.company_id = c.company_id
                    JOIN
                        tally_voucher_types vt ON v.voucher_type_id = vt.voucher_type_id
                    JOIN
                        tally_voucher_heads vh ON v.voucher_id = vh.voucher_id
                    JOIN
                        tally_ledgers l ON vh.ledger_id = l.ledger_id
                    WHERE
                        v.is_optional = 1
                        AND l.company_id IN ({$companyIdsList})
                        AND ({$startDateFilter} IS NULL OR v.voucher_date >= {$startDateFilter})
                        AND ({$endDateFilter} IS NULL OR v.voucher_date <= {$endDateFilter})  
                    GROUP BY
                        v.voucher_id,
                        v.voucher_date,
                        c.company_name,
                        vt.voucher_type_name,
                        v.voucher_number
                    ORDER BY
                        v.voucher_date ASC
                ";


            Log::info("Daybook Query", ['sql' => $sql]);

            $dayBook = DB::select(DB::raw($sql));

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportCancelledController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($dayBook)
                ->addIndexColumn()
                ->addColumn('credit', function ($data) {
                    return indian_format(abs($data->total_credit));
                })
                ->addColumn('debit', function ($data) {
                    return indian_format(abs($data->total_debit));
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportCancelledController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }
}
