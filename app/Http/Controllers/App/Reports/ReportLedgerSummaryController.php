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

class ReportLedgerSummaryController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.reports.ledgerSummary.index');
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

    //         $startDateFilter = $startDate ? "'{$startDate}'" : 'NULL';
    //         $endDateFilter = $endDate ? "'{$endDate}'" : 'NULL';

    //         $companyIdsList = implode(',', $companyIds);

    //         $sql = "
    //                 WITH RECURSIVE ledger_group_hierarchy AS (
    //                 -- Base case: select top-level ledger groups (parent IS NULL)
    //                 SELECT
    //                     lg.ledger_group_id,
    //                     lg.ledger_group_name,
    //                     lg.parent AS parent_group_name,
    //                     CAST(NULL AS SIGNED) AS parent_ledger_group_id,
    //                     CAST(lg.ledger_group_name AS CHAR(1000)) AS full_group_path,
    //                     0 AS level
    //                 FROM
    //                     tally_ledger_groups lg
    //                 WHERE
    //                     (lg.parent IS NULL OR COALESCE(lg.parent, '') = '')
    //                     AND lg.company_id = ({$companyIdsList})  -- Replace with your company_id

    //                 UNION ALL

    //                 -- Recursive case: select child ledger groups
    //                 SELECT
    //                     lg_child.ledger_group_id,
    //                     lg_child.ledger_group_name,
    //                     lg_child.parent AS parent_group_name,
    //                     lg_h.ledger_group_id AS parent_ledger_group_id,
    //                     CAST(CONCAT(lg_h.full_group_path, ' > ', lg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
    //                     lg_h.level + 1 AS level
    //                 FROM
    //                     tally_ledger_groups lg_child
    //                 INNER JOIN
    //                     ledger_group_hierarchy lg_h ON lg_child.parent = lg_h.ledger_group_name
    //                 WHERE
    //                     lg_child.company_id = ({$companyIdsList})  -- Replace with your company_id
    //             ),
    //             ledger_balances AS (
    //                 -- Compute balances for each ledger
    //                 SELECT
    //                     l.ledger_id,
    //                     l.ledger_name,
    //                     l.ledger_group_id,
    //                     (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS adjusted_opening_balance,
    //                     ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
    //                     IFNULL(vai.credit_amount, 0) AS total_credit,
    //                     (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0) + IFNULL(vai.total_amount, 0)) AS closing_balance
    //                 FROM
    //                     tally_ledgers l
    //                 LEFT JOIN
    //                     (
    //                         SELECT
    //                             vh.ledger_id,
    //                             SUM(vh.amount) AS total_amount
    //                         FROM
    //                             tally_voucher_heads vh
    //                         INNER JOIN
    //                             tally_vouchers v ON vh.voucher_id = v.voucher_id
    //                         WHERE
    //                             v.voucher_date < {$startDateFilter}  -- Replace with your start date
    //                             AND (v.is_optional = 0 OR v.is_optional IS NULL)
    //                             AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
    //                             AND v.company_id = ({$companyIdsList})
    //                         GROUP BY
    //                             vh.ledger_id
    //                     ) vab ON vab.ledger_id = l.ledger_id
    //                 LEFT JOIN
    //                     (
    //                         SELECT
    //                             vh.ledger_id,
    //                             SUM(vh.amount) AS total_amount,
    //                             SUM(CASE WHEN vh.amount < 0 THEN vh.amount ELSE 0 END) AS debit_amount,
    //                             SUM(CASE WHEN vh.amount > 0 THEN vh.amount ELSE 0 END) AS credit_amount
    //                         FROM
    //                             tally_voucher_heads vh
    //                         INNER JOIN
    //                             tally_vouchers v ON vh.voucher_id = v.voucher_id
    //                         WHERE
    //                             v.is_optional = 0 OR v.is_optional IS NULL
    //                             AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
    //                             AND (
    //                                     v.voucher_date BETWEEN {$startDateFilter} AND {$endDateFilter}
    //                                     OR ({$endDateFilter} IS NULL AND {$startDateFilter} IS NULL)
    //                                 )
    //                             AND v.company_id = ({$companyIdsList})
    //                         GROUP BY
    //                             vh.ledger_id
    //                     ) vai ON vai.ledger_id = l.ledger_id
    //                 WHERE
    //                     l.company_id = ({$companyIdsList})
    //             ),
    //             ledger_hierarchy AS (
    //                 -- Combine ledgers with their full hierarchical paths and balances
    //                 SELECT
    //                     lg_h.level + 1 AS level,
    //                     CONCAT(lg_h.full_group_path, ' > ', l.ledger_name) AS hierarchy,
    //                     'Ledger' AS type,
    //                     l.ledger_id AS id,
    //                     l.ledger_name AS name,
    //                     l.ledger_guid,
    //                     lb.adjusted_opening_balance AS opening_balance,
    //                     lb.total_debit,
    //                     lb.total_credit,
    //                     lb.closing_balance,
    //                     l.ledger_name AS final_ledger_name
    //                 FROM
    //                     ledger_group_hierarchy lg_h
    //                 INNER JOIN
    //                     tally_ledgers l ON l.ledger_group_id = lg_h.ledger_group_id
    //                 INNER JOIN
    //                     ledger_balances lb ON lb.ledger_id = l.ledger_id
    //             )
    //             -- Final selection
    //             SELECT
    //                 level,
    //                 hierarchy,
    //                 type,
    //                 id,
    //                 name,
    //                 ledger_guid,
    //                 opening_balance,
    //                 total_debit,
    //                 total_credit,
    //                 closing_balance,
    //                 final_ledger_name
    //             FROM
    //                 ledger_hierarchy
    //             ORDER BY
    //                 hierarchy;

    //             ";

    //         Log::info("Balance Sheet Query", ['sql' => $sql]);

    //         $balanceSheet = DB::select(DB::raw($sql));
    //         // dd($balanceSheet);

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

    //         $dataTable = DataTables::of($balanceSheet)
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
    //         Log::info('Total end execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

    //         return $dataTable;
    //     }
    // }


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
                    default:
                        break;
                }
            }

            $companyIdsList = implode(',', $companyIds);

            Log::info("Calling Balance Sheet Stored Procedure", [
                'company_ids' => $companyIdsList,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            try {
                $balanceSheet = DB::select('CALL get_ledger_summary_data(?, ?, ?)', [
                    $companyIdsList,
                    $startDate,
                    $endDate
                ]);
            } catch (\Exception $e) {
                Log::error("Error executing stored procedure get_ledger_summary_data", [
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'error' => 'An error occurred while fetching the balance sheet data.'
                ], 500);
            }

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Stored procedure execution time for ReportLedgerSummaryController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

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
            Log::info('Total end execution time for ReportLedgerSummaryController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }

}
