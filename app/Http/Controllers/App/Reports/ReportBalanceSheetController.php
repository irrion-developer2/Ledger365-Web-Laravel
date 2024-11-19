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
        $companyIds = $this->reportService->companyData(); // Get dynamic company IDs

        if ($request->ajax()) {
            $startTime = microtime(true);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');

            // Handle date range logic
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
                        $startDate = null;
                        $endDate = null;
                        break;
                }
            }

            // Prepare dynamic placeholders for company IDs
            $placeholders = implode(',', array_fill(0, count($companyIds), '?'));

            $query = <<<SQL
            WITH RECURSIVE ledger_group_hierarchy AS (
                SELECT
                    lg.ledger_group_id,
                    lg.ledger_group_name,
                    lg.parent,
                    CAST(lg.ledger_group_name AS CHAR(1000)) AS full_group_path,
                    0 AS level
                FROM
                    tally_ledger_groups lg
                WHERE
                    lg.ledger_group_name IN ('Capital Account', 'Loans (Liability)', 'Current Liabilities', 'Fixed Assets', 'Investments', 'Current Assets')
                    AND lg.company_id IN ($placeholders)

                UNION ALL

                SELECT
                    lg_child.ledger_group_id,
                    lg_child.ledger_group_name,
                    lg_child.parent,
                    CAST(CONCAT(lg_h.full_group_path, ' > ', lg_child.ledger_group_name) AS CHAR(1000)) AS full_group_path,
                    lg_h.level + 1 AS level
                FROM
                    tally_ledger_groups lg_child
                INNER JOIN
                    ledger_group_hierarchy lg_h ON lg_child.parent = lg_h.ledger_group_name
                WHERE
                    lg_child.company_id IN ($placeholders)
            ),
            voucher_amounts_before AS (
                SELECT
                    vh.ledger_id,
                    SUM(vh.amount) AS total_amount
                FROM
                    tally_voucher_heads vh
                INNER JOIN
                    tally_vouchers v ON vh.voucher_id = v.voucher_id
                WHERE
                    v.voucher_date < ?
                    AND (v.is_optional = 0 OR v.is_optional IS NULL)
                    AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                    AND v.company_id IN ($placeholders)
                GROUP BY
                    vh.ledger_id
            ),
            voucher_amounts_in_range AS (
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
                    v.voucher_date BETWEEN ? AND ?
                    AND (v.is_optional = 0 OR v.is_optional IS NULL)
                    AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                    AND v.company_id IN ($placeholders)
                GROUP BY
                    vh.ledger_id
            ),
            ledger_balances AS (
                SELECT
                    l.ledger_id,
                    l.ledger_name,
                    lg_h.full_group_path AS ledger_group_hierarchy,
                    (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0)) AS adjusted_opening_balance,
                    ABS(IFNULL(vai.debit_amount, 0)) AS total_debit,
                    IFNULL(vai.credit_amount, 0) AS total_credit,
                    (IFNULL(l.opening_balance, 0) + IFNULL(vab.total_amount, 0) + IFNULL(vai.total_amount, 0)) AS closing_balance
                FROM
                    ledger_group_hierarchy lg_h
                INNER JOIN
                    tally_ledgers l ON l.ledger_group_id = lg_h.ledger_group_id
                LEFT JOIN
                    voucher_amounts_before vab ON vab.ledger_id = l.ledger_id
                LEFT JOIN
                    voucher_amounts_in_range vai ON vai.ledger_id = l.ledger_id
                WHERE
                    l.company_id IN ($placeholders)
            )
            SELECT
                lb.ledger_group_hierarchy,
                SUM(lb.adjusted_opening_balance) AS opening_balance,
                SUM(lb.total_debit) AS total_debit,
                SUM(lb.total_credit) AS total_credit,
                SUM(lb.closing_balance) AS closing_balance
            FROM
                ledger_balances lb
            GROUP BY
                lb.ledger_group_hierarchy
            ORDER BY
                lb.ledger_group_hierarchy;
        SQL;

            // Combine bindings
            $bindings = array_merge(
                $companyIds, // For ledger_group_hierarchy
                $companyIds, // For recursive ledger_group_hierarchy
                [$startDate], // For voucher_amounts_before
                $companyIds, // For voucher_amounts_before company_ids
                [$startDate, $endDate], // For voucher_amounts_in_range
                $companyIds, // For voucher_amounts_in_range company_ids
                $companyIds, // For voucher_amounts_in_range company_ids
            );

            // Execute the query
            $results = DB::select($query, $bindings);

            // log query with proper binding in them
            Log::info('BalanceSheet Query');
            Log::info($this->reportService->getFinalQuery($query, $bindings));

            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            $dataTable = DataTables::of($results)
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
            Log::info('Execution time for getData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }

    // public function index()
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     $company = TallyCompany::where('guid', $companyGuids)->first();

    //     return view ('app.reports.balanceSheet.index', compact('company'));
    // }

    // public function getData(Request $request)
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $accountTypeCases = '
    //             CASE
    //                 WHEN name LIKE "%liabilities%" THEN "Liability"
    //                 WHEN name LIKE "%liability%" THEN "Liability"
    //                 WHEN name LIKE "%branch / divisions%" THEN "Liability"
    //                 WHEN name LIKE "%suspense a/c%" THEN "Liability"
    //                 WHEN name LIKE "%capital account%" THEN "Liability"

    //                 WHEN name LIKE "%assets%" THEN "Asset"
    //                 WHEN name LIKE "%asset%" THEN "Asset"
    //                 WHEN name LIKE "%investments%" THEN "Asset"

    //                 WHEN name LIKE "%income%" THEN "Revenue"
    //                 WHEN name LIKE "%revenue%" THEN "Revenue"
    //                 WHEN name LIKE "%sales accounts%" THEN "Revenue"

    //                 WHEN name LIKE "%expense%" THEN "Expense"
    //                 WHEN name LIKE "%purchase%" THEN "Expense"

    //                 ELSE "Other"
    //             END as account_type
    //         ';

    //         $Balancequery = TallyLedgerGroup::where(function($query) {
    //                         $query->where('parent', '')->orWhereNull('parent');
    //                     })
    //                     ->whereIn('company_guid', $companyGuids)
    //                     ->selectRaw("guid, name, parent, company_guid, $accountTypeCases");

    //         Log::info("BalanceSheet Query");
    //         Log::info($this->reportService->getFinalQuery($Balancequery));

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

    //         $dataTable = DataTables::of($Balancequery)
    //             ->addIndexColumn()
    //             ->editColumn('amount', function ($data) use ($companyGuids) {

    //                 $name = $data->name;

    //                 foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
    //                     if (strpos($name, $pattern) !== false) {
    //                         $name = $normalized;
    //                         break;
    //                     }
    //                 }

    //                 $groupLedgerIdsQuery = TallyLedgerGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
    //                 $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');

    //                 if ($groupLedgerIds->isNotEmpty()) {
    //                     $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
    //                             ->whereIn('company_guid', $companyGuids)
    //                             ->pluck('guid');
    //                 } else {
    //                     $ledgerIds = TallyLedger::where('parent', $name)
    //                             ->whereIn('company_guid', $companyGuids)
    //                             ->pluck('guid');
    //                 }

    //                 $allLedgerIds = $ledgerIds->unique();

    //                 if ($allLedgerIds->isEmpty()) {
    //                     return '-';
    //                 }

    //                 $totalAmount = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
    //                                                 ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds)
    //                                                 ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                                                 ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                                                 ->sum('tally_voucher_heads.amount');

    //                 if ($totalAmount == 0) {
    //                     return '-';
    //                 }


    //                 // dd($totalAmount);
    //                 return indian_format(abs($totalAmount));
    //             })
    //             ->filter(function ($query) {
    //                 $query->get()->filter(function ($item) {
    //                     $name = strtolower($item->name);
    //                     return strpos($name, 'liabilities') !== false || strpos($name, 'liability') !== false;
    //                 });
    //             })
    //             ->filter(function ($query) {
    //                 $query->get()->filter(function ($item) {
    //                     $name = strtolower($item->name);
    //                     return strpos($name, 'assets') !== false || strpos($name, 'asset') !== false;
    //                 });
    //             })

    //             ->make(true);

    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

    //         return $dataTable;
    //     }
    // }

}
