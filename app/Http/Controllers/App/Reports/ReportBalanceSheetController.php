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
                    WITH RECURSIVE ledger_group_hierarchy AS (
                    SELECT
                        tlg.ledger_group_id,
                        tlg.ledger_group_name,
                        tlg.parent,
                        CAST(tlg.ledger_group_name AS CHAR(1000)) AS full_group_path,
                        0 AS level
                    FROM
                        tally_ledger_groups tlg
                    WHERE
                        tlg.ledger_group_name IN ('Suspense A/c', 'Capital Account', 'Loans (Liability)', 'Current Liabilities', 'Fixed Assets', 'Investments', 'Current Assets')
                        AND tlg.company_id = ({$companyIdsList})
                    UNION ALL
                    SELECT
                        tlg_child.ledger_group_id,
                        tlg_child.ledger_group_name,
                        tlg_child.parent,
                        CAST(CONCAT(tlg_h.full_group_path, ' > ', tlg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
                        tlg_h.level + 1 AS level
                    FROM
                        tally_ledger_groups tlg_child
                    INNER JOIN
                        ledger_group_hierarchy tlg_h ON tlg_child.parent = tlg_h.ledger_group_name
                    WHERE
                        tlg_child.company_id = ({$companyIdsList})
                        AND tlg_h.level < 10
                ),
                voucher_amounts_before AS (
                    SELECT
                        tvh.ledger_id,
                        SUM(tvh.amount) AS total_amount
                    FROM
                        tally_voucher_heads tvh
                    INNER JOIN
                        tally_vouchers tv ON tvh.voucher_id = tv.voucher_id
                    WHERE 
                        (tv.is_optional = 0 OR tv.is_optional IS NULL)
                        AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                        AND tv.company_id = ({$companyIdsList})
                    GROUP BY
                        tvh.ledger_id
                ),
                voucher_amounts_in_range AS (
                    SELECT
                        tvh.ledger_id,
                        SUM(tvh.amount) AS total_amount,
                        SUM(CASE WHEN tvh.amount < 0 THEN ABS(tvh.amount) ELSE 0 END) AS debit_amount,
                        SUM(CASE WHEN tvh.amount > 0 THEN tvh.amount ELSE 0 END) AS credit_amount
                    FROM
                        tally_voucher_heads tvh
                    INNER JOIN
                        tally_vouchers tv ON tvh.voucher_id = tv.voucher_id
                    WHERE 
                        (tv.is_optional = 0 OR tv.is_optional IS NULL)
                        AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                        AND tv.company_id = ({$companyIdsList})
                        
                        AND ({$startDateFilter} IS NULL OR tv.voucher_date >= {$startDateFilter})
                        AND ({$endDateFilter} IS NULL OR tv.voucher_date <= {$endDateFilter})
                    GROUP BY
                        tvh.ledger_id
                ),
                ledger_balances AS (
                    SELECT
                        tl.ledger_id,
                        tl.ledger_name,
                        tlg_h.full_group_path AS ledger_group_hierarchy,
                        (IFNULL(tl.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS closing_balance,
                        ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
                        IFNULL(vai.credit_amount, 0) AS total_credit,
                        (IFNULL(tl.opening_balance, 0) + IFNULL(vab.total_amount, 0) + ABS(IFNULL(vai.debit_amount, 0)) - IFNULL(vai.credit_amount, 0)) AS opening_balance
                    FROM
                        ledger_group_hierarchy tlg_h
                    INNER JOIN
                        tally_ledgers tl ON tl.ledger_group_id = tlg_h.ledger_group_id
                    LEFT JOIN
                        voucher_amounts_before vab ON vab.ledger_id = tl.ledger_id
                    LEFT JOIN
                        voucher_amounts_in_range vai ON vai.ledger_id = tl.ledger_id
                    WHERE
                        tl.company_id = ({$companyIdsList})
                )
                SELECT
                    lb.ledger_group_hierarchy,
                    SUM(lb.opening_balance) AS opening_balance,
                    SUM(lb.total_debit) AS total_debit,
                    SUM(lb.total_credit) AS total_credit,
                    SUM(lb.closing_balance) AS closing_balance
                FROM
                    ledger_balances lb
                GROUP BY
                    lb.ledger_group_hierarchy
                ORDER BY
                    lb.ledger_group_hierarchy;
            ";

            Log::info("Balance Sheet Query", ['sql' => $sql]);
    
            $balanceSheet = DB::select(DB::raw($sql));
            // dd($balanceSheet);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($balanceSheet)
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
            Log::info('Total end execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
