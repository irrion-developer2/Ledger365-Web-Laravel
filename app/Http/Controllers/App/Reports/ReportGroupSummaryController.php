<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;
use App\Models\TallyLedger;
use Illuminate\Http\Request;

class ReportGroupSummaryController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.reports.groupSummary.index');
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
                    WITH RECURSIVE ledger_group_hierarchy AS (
                        -- Base case: select top-level ledger groups (parent IS NULL)
                        SELECT
                            lg.ledger_group_id,
                            lg.ledger_group_name,
                            lg.parent AS parent_group_name,
                            CAST(NULL AS SIGNED) AS parent_ledger_group_id,
                            CAST(lg.ledger_group_name AS CHAR(1000)) AS full_group_path,
                            0 AS level
                        FROM
                            tally_ledger_groups lg
                        WHERE
                            lg.parent IS NULL
                            AND lg.company_id = ({$companyIdsList})  -- Replace with your company_id

                        UNION ALL

                        -- Recursive case: select child ledger groups
                        SELECT
                            lg_child.ledger_group_id,
                            lg_child.ledger_group_name,
                            lg_child.parent AS parent_group_name,
                            lg_h.ledger_group_id AS parent_ledger_group_id,
                            CAST(CONCAT(lg_h.full_group_path, ' > ', lg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
                            lg_h.level + 1 AS level
                        FROM
                            tally_ledger_groups lg_child
                        INNER JOIN
                            ledger_group_hierarchy lg_h ON lg_child.parent = lg_h.ledger_group_name
                        WHERE
                            lg_child.company_id = ({$companyIdsList})  -- Replace with your company_id
                    ),
                    ledger_hierarchy AS (
                        -- Assign full_group_path to each ledger
                        SELECT
                            l.ledger_id,
                            l.ledger_name,
                            l.ledger_group_id,
                            CONCAT(lg_h.full_group_path, ' > ', l.ledger_name) AS full_ledger_path
                        FROM
                            tally_ledgers l
                        INNER JOIN
                            ledger_group_hierarchy lg_h ON l.ledger_group_id = lg_h.ledger_group_id
                        WHERE
                            l.company_id = ({$companyIdsList})  -- Replace with your company_id
                    ),
                    ledger_balances AS (
                        -- Compute balances for each ledger
                        SELECT
                            lh.ledger_id,
                            lh.full_ledger_path,
                            (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS adjusted_opening_balance,
                            ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
                            IFNULL(vai.credit_amount, 0) AS total_credit,
                            (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0) + IFNULL(vai.total_amount, 0)) AS closing_balance
                        FROM
                            ledger_hierarchy lh
                        INNER JOIN
                            tally_ledgers l ON lh.ledger_id = l.ledger_id
                        LEFT JOIN
                            (
                                SELECT
                                    vh.ledger_id,
                                    SUM(vh.amount) AS total_amount
                                FROM
                                    tally_voucher_heads vh
                                INNER JOIN
                                    tally_vouchers v ON vh.voucher_id = v.voucher_id
                                WHERE
                                    v.voucher_date < {$startDateFilter}  -- Replace with your start date
                                    AND (v.is_optional = 0 OR v.is_optional IS NULL)
                                    AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                                    AND v.company_id = ({$companyIdsList})  -- Replace with your company_id
                                GROUP BY
                                    vh.ledger_id
                            ) vab ON vab.ledger_id = l.ledger_id
                        LEFT JOIN
                            (
                                SELECT
                                    vh.ledger_id,
                                    SUM(vh.amount) AS total_amount,
                                    SUM(CASE WHEN vh.amount < 0 THEN vh.amount ELSE 0 END) AS debit_amount,
                                    SUM(CASE WHEN vh.amount > 0 THEN vh.amount ELSE 0 END) AS credit_amount
                                FROM
                                    tally_voucher_heads vh
                                INNER JOIN
                                    tally_vouchers v ON vh.voucher_id = v.voucher_id
                                WHERE
                                    v.is_optional = 0 OR v.is_optional IS NULL
                                    AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                                    AND v.company_id = ({$companyIdsList})  -- Replace with your company_id
                                    AND (
                                            v.voucher_date BETWEEN {$startDateFilter} AND {$endDateFilter}
                                            OR ({$endDateFilter} IS NULL AND {$startDateFilter} IS NULL)
                                        )
                                GROUP BY
                                    vh.ledger_id
                            ) vai ON vai.ledger_id = l.ledger_id
                    ),
                    group_balances AS (
                        -- Compute balances for groups by summing balances of all ledgers under them
                        SELECT
                            lg_h.level,
                            lg_h.full_group_path AS hierarchy,
                            'Group' AS type,
                            lg_h.ledger_group_id AS id,
                            lg_h.ledger_group_name AS name,
                            SUM(lb.adjusted_opening_balance) AS opening_balance,
                            SUM(lb.total_debit) AS total_debit,
                            SUM(lb.total_credit) AS total_credit,
                            SUM(lb.closing_balance) AS closing_balance
                        FROM
                            ledger_group_hierarchy lg_h
                        LEFT JOIN
                            ledger_balances lb ON lb.full_ledger_path LIKE CONCAT(lg_h.full_group_path, '%')
                        GROUP BY
                            lg_h.level,
                            lg_h.full_group_path,
                            lg_h.ledger_group_id,
                            lg_h.ledger_group_name
                    )
                    -- Final selection
                    SELECT
                        level,
                        hierarchy,
                        type,
                        id,
                        name,
                        opening_balance,
                        total_debit,
                        total_credit,
                        closing_balance
                    FROM
                        group_balances
                    ORDER BY
                        hierarchy;
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
