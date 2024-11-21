<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyLedgerGroup;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ReportBalanceSheetProfitLossController extends Controller
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

        return view ('app.reports.balanceSheet._profit-loss', compact('company'));
    }

    public function OpeningGetData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $openingValueSum = TallyItem::whereIn('company_guid', $companyGuids)->sum('opening_value');
            $data = [
                [
                    'name' => 'Opening Stock',
                    'opening_value' => indian_format(abs($openingValueSum))
                ]
            ];
            return response()->json(['data' => $data]);
        }
    }


    // public function getData(Request $request)
    // {
    //     $companyIds = $this->reportService->companyData();

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $desiredParentGroups = ['Purchase Accounts', 'Sales Accounts', 'Direct Expenses', 'Direct Incomes', 'Indirect Expenses', 'Indirect Incomes'];


    //         $transactionsSubquery = DB::table('tally_voucher_heads as tvh')
    //         ->select(
    //             'tvh.ledger_id',
    //             DB::raw('SUM(CASE WHEN tvh.amount < 0 THEN ABS(tvh.amount) ELSE 0 END) AS total_debit'),
    //             DB::raw('SUM(CASE WHEN tvh.amount > 0 THEN tvh.amount ELSE 0 END) AS total_credit'),
    //             DB::raw('SUM(tvh.amount) AS net_change')
    //         )
    //         ->join('tally_vouchers as tv', 'tvh.voucher_id', '=', 'tv.voucher_id')
    //         ->where(function ($query) {
    //             $query->where('tv.is_optional', 0)
    //                   ->orWhereNull('tv.is_optional');
    //         })
    //         ->where(function ($query) {
    //             $query->where('tv.is_cancelled', 0)
    //                   ->orWhereNull('tv.is_cancelled');
    //         })
    //         ->groupBy('tvh.ledger_id');

    //     $latestVoucherDateSubquery = DB::table('tally_voucher_heads as tvh')
    //         ->select(
    //             'tvh.ledger_id',
    //             DB::raw('MAX(tv.voucher_date) AS latest_voucher_date')
    //         )
    //         ->join('tally_vouchers as tv', 'tvh.voucher_id', '=', 'tv.voucher_id')
    //         ->where(function ($query) {
    //             $query->where('tv.is_optional', 0)
    //                   ->orWhereNull('tv.is_optional');
    //         })
    //         ->where(function ($query) {
    //             $query->where('tv.is_cancelled', 0)
    //                   ->orWhereNull('tv.is_cancelled');
    //         })
    //         ->groupBy('tvh.ledger_id');

    //     $ledgerSummaryQuery = DB::table('tally_ledger_groups as tlg')
    //         ->select(
    //             DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name) AS parent_group_name'),
    //             DB::raw('IFNULL(SUM(tl.opening_balance), 0) AS total_opening_balance'),
    //             DB::raw('IFNULL(SUM(tr.total_debit), 0) AS total_debit'),
    //             DB::raw('IFNULL(SUM(tr.total_credit), 0) AS total_credit'),
    //             DB::raw('IFNULL(SUM(tl.opening_balance), 0) + IFNULL(SUM(tr.net_change), 0) AS total_closing_balance'),
    //             DB::raw('MAX(lv.latest_voucher_date) AS latest_voucher_date')
    //         )
    //         ->leftJoin('tally_ledgers as tl', 'tl.ledger_group_id', '=', 'tlg.ledger_group_id')
    //         ->leftJoinSub($transactionsSubquery, 'tr', function ($join) {
    //             $join->on('tl.ledger_id', '=', 'tr.ledger_id');
    //         })
    //         ->leftJoinSub($latestVoucherDateSubquery, 'lv', function ($join) {
    //             $join->on('tl.ledger_id', '=', 'lv.ledger_id');
    //         })
    //         ->whereIn('tl.company_id', $companyIds)
    //         ->whereIn(DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name)'), $desiredParentGroups)
    //         ->groupBy(DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name)'))
    //         ->orderBy('parent_group_name');

    //         $startDate = $request->get('start_date');
    //         $endDate = $request->get('end_date');
    //         $customDateRange = $request->get('custom_date_range');

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

    //         if ($startDate && $endDate) {
    //             $ledgerSummaryQuery->whereBetween('lv.latest_voucher_date', [$startDate, $endDate]);
    //         }

    //         $ledgerSummary = $ledgerSummaryQuery->get();

    //         Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
    //         Log::info('Start date:', ['startDate' => $startDate]);
    //         Log::info('End date:', ['endDate' => $endDate]);

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for ReportGroupSummaryController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

    //         $dataTable = DataTables::of($ledgerSummary)
    //             ->addIndexColumn()
    //             ->addColumn('opening_balance', function ($data) {
    //                 return indian_format($data->total_opening_balance);
    //             })
    //             ->addColumn('total_debit', function ($data) {
    //                 return indian_format($data->total_debit);
    //             })
    //             ->addColumn('total_credit', function ($data) {
    //                 return indian_format($data->total_credit);
    //             })
    //             ->addColumn('closing_balance', function ($data) {
    //                 return indian_format($data->total_closing_balance);
    //             })
    //             ->make(true);

    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for ReportGroupSummaryController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

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
                        tlg.ledger_group_name IN ('Purchase Accounts', 'Sales Accounts', 'Direct Expenses', 'Direct Incomes', 'Indirect Expenses', 'Indirect Incomes')
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


    public function getExpenseData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $accountTypeCases = '
                CASE
                    WHEN name LIKE "%liabilities%" THEN "Liability"
                    WHEN name LIKE "%liability%" THEN "Liability"
                    WHEN name LIKE "%branch / divisions%" THEN "Liability"
                    WHEN name LIKE "%suspense a/c%" THEN "Liability"
                    WHEN name LIKE "%capital account%" THEN "Liability"

                    WHEN name LIKE "%assets%" THEN "Asset"
                    WHEN name LIKE "%asset%" THEN "Asset"
                    WHEN name LIKE "%investments%" THEN "Asset"

                    WHEN name LIKE "%income%" THEN "Revenue"
                    WHEN name LIKE "%revenue%" THEN "Revenue"
                    WHEN name LIKE "%sales accounts%" THEN "Revenue"

                    WHEN name LIKE "%expense%" THEN "Expense"
                    WHEN name LIKE "%purchase%" THEN "Expense"
                    ELSE "Other"
                END as account_type
            ';

            $Balancequery = TallyLedgerGroup::where(function($query) {
                                $query->where('parent', '')->orWhereNull('parent');
                            })
                            ->whereIn('company_guid', $companyGuids)
                            ->selectRaw("guid, name, parent, company_guid, $accountTypeCases");

            Log::info("BalanceSheet Query");
            Log::info($this->reportService->getFinalQuery($Balancequery));

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportBalanceSheetProfitLossController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($Balancequery)
                ->addIndexColumn()
                ->editColumn('amount', function ($data) use ($companyGuids) {

                    $name = $data->name;

                    foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
                        if (strpos($name, $pattern) !== false) {
                            $name = $normalized;
                            break;
                        }
                    }

                    $groupLedgerIdsQuery = TallyLedgerGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
                    $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');

                    if ($groupLedgerIds->isNotEmpty()) {
                        $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    } else {
                        $ledgerIds = TallyLedger::where('parent', $name)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    }

                    $allLedgerIds = $ledgerIds->unique();

                    if ($allLedgerIds->isEmpty()) {
                        return '-';
                    }

                    $totalAmount = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                    ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds)
                                                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                                                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                                                    ->sum('tally_voucher_heads.amount');

                    if ($totalAmount == 0) {
                        return '-';
                    }

                    return indian_format(abs($totalAmount));
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'expense') !== false || strpos($name, 'purchase') !== false;
                    });
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'income') !== false || strpos($name, 'revenue') !== false;
                    });
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportBalanceSheetProfitLossController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }

    public function getClosingStockData(Request $request)
    {
        $startTime = microtime(true);

        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            // Fetch Tally Items and key by GUID
            $tallyItems = TallyItem::whereIn('company_guid', $companyGuids)
                ->select('id', 'guid', 'name', 'opening_balance', 'opening_value', 'company_guid')
                ->get()
                ->keyBy('guid');

            $itemGuids = $tallyItems->keys();

            // Fetch sums per item
            $sums = TallyVoucherItem::whereIn('stock_item_guid', $itemGuids)
                ->join('tally_vouchers', 'tally_voucher_items.tally_voucher_id', '=', 'tally_vouchers.id')
                ->whereIn('tally_vouchers.voucher_type', ['Sales', 'Debit Note', 'Purchase', 'Credit Note'])
                ->select(
                    'tally_voucher_items.stock_item_guid',
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Sales' THEN billed_qty ELSE 0 END) as total_sales_qty"),
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Debit Note' THEN billed_qty ELSE 0 END) as total_debit_note_qty"),
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Purchase' THEN billed_qty ELSE 0 END) as total_purchase_qty"),
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Credit Note' THEN billed_qty ELSE 0 END) as total_credit_note_qty"),
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Purchase' THEN amount ELSE 0 END) as total_purchase_amount"),
                    DB::raw("SUM(CASE WHEN tally_vouchers.voucher_type = 'Debit Note' THEN amount ELSE 0 END) as total_debit_note_amount")
                )
                ->groupBy('tally_voucher_items.stock_item_guid')
                ->get()
                ->keyBy('stock_item_guid');

            $stock_value = 0; // Initialize stock_value

            foreach ($tallyItems as $guid => $entry) {

                $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
                $openingValue = $this->reportService->extractNumericValue($entry->opening_value);

                // Get sums for this stock item
                $sumsEntry = $sums->get($guid);

                // Initialize sums if not found
                $stockItemVoucherSaleBalance = $sumsEntry->total_sales_qty ?? 0;
                $stockItemVoucherDebitNoteBalance = $sumsEntry->total_debit_note_qty ?? 0;
                $stockItemVoucherPurchaseBalance = $sumsEntry->total_purchase_qty ?? 0;
                $stockItemVoucherCreditNoteBalance = $sumsEntry->total_credit_note_qty ?? 0;
                $stockItemVoucherPurchaseAmount = $sumsEntry->total_purchase_amount ?? 0;
                $stockItemVoucherDebitNoteAmount = $sumsEntry->total_debit_note_amount ?? 0;

                // Calculate the balance of stock on hand
                $stockItemVoucherBalance = ($stockItemVoucherSaleBalance - $stockItemVoucherCreditNoteBalance)
                                        - ($stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance);

                $openingAmount = ($stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount);

                $finalOpeningValue = $openingValue - $openingAmount;
                $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;

                if ($finalOpeningBalance != 0) {
                    $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                    $stockItemVoucherSaleValue = indian_format($stockItemVoucherSaleValue);

                    // Use the correct variable for the stock on hand balance
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherBalance;
                    $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;

                    $stock_value += $stockOnHandValue;
                }
            }

            $data = [
                [
                    'name' => 'Closing Stock',
                    'closing_value' => indian_format($stock_value)
                ]
            ];

            $endTime = microtime(true);

            \Log::info('getClosingStockData execution time: ' . ($endTime - $startTime) . ' seconds.');

            return response()->json(['data' => $data]);
        }
    }


}
