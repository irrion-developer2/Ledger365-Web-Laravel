<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyVoucher;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class ReportDayBookController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.reports.dayBook.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

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
                    case 'all':
                        break;
                }
            }

            $startDateFilter = $startDate ? "'{$startDate}'" : 'NULL';
            $endDateFilter = $endDate ? "'{$endDate}'" : 'NULL';
    
            $companyIdsList = implode(',', $companyIds);

            $sql = "
                    SELECT 
                        tv.voucher_date,
                        c.company_name,
                        tl.ledger_name,
                        tvt.voucher_type_name,
                        tv.voucher_number,
                        SUM(CASE WHEN tvh.entry_type = 'Debit' THEN tvh.amount ELSE 0 END) AS `total_debit`,
                        SUM(CASE WHEN tvh.entry_type = 'Credit' THEN tvh.amount ELSE 0 END) AS `total_credit`
                    FROM 
                        tally_vouchers tv
                    JOIN 
                        tally_voucher_heads tvh ON tv.voucher_id = tvh.voucher_id
                    JOIN 
                        tally_ledgers tl ON tvh.ledger_id = tl.ledger_id
                    JOIN 
                        tally_voucher_types tvt ON tv.voucher_type_id = tvt.voucher_type_id
                    LEFT JOIN
                        tally_companies c
                        ON tv.company_id = c.company_id 
                    WHERE
                        (tv.is_optional = 0 OR tv.is_optional IS NULL)
                        AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                        AND tv.company_id IN ({$companyIdsList})
                        AND tvh.is_party_ledger = 1
                        AND ({$startDateFilter} IS NULL OR tv.voucher_date >= {$startDateFilter})
                        AND ({$endDateFilter} IS NULL OR tv.voucher_date <= {$endDateFilter})
                    GROUP BY 
                        tv.voucher_date, 
                        tl.ledger_name, 
                        tvt.voucher_type_name, 
                        tv.voucher_number
                    ORDER BY 
                        tv.voucher_date, 
                        tv.voucher_number
                ";


            Log::info("Daybook Query", ['sql' => $sql]);

            $dayBook = DB::select(DB::raw($sql));

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportDayBookController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

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
            Log::info('Total end execution time for ReportDayBookController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }
}
