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
            $ledgerGroupHierarchy = ['Purchase Accounts', 'Sales Accounts', 'Direct Expenses', 'Direct Incomes', 'Indirect Expenses', 'Indirect Incomes'];
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
                    'p_types' => $type,
                    'p_hierarchy_names' => $ledgerGroupHierarchy
                ]
            ]);

            try {
                $dayBook = DB::select($sql, [
                    $companyIdsList,    
                    $startDate,         
                    $endDate,      
                    $type,    
                    $ledgerGroupHierarchy,
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
            Log::info('Total first DB request execution time for ReportBalanceSheetProfitLossController.getData:', [
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
            Log::info('Total end execution time for ReportBalanceSheetProfitLossController.getData:', [
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
    //         $ledgerGroupName = ['Purchase Accounts', 'Sales Accounts', 'Direct Expenses', 'Direct Incomes', 'Indirect Expenses', 'Indirect Incomes'];

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
    //         Log::info('Total first DB request execution time for ReportBalanceSheetProfitLossController.getData:', [
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
    //         Log::info('Total end execution time for ReportBalanceSheetProfitLossController.getData:', [
    //             'time_taken' => $executionTime . ' seconds'
    //         ]);

    //         return $dataTable;
    //     }

    //     return response()->json(['message' => 'Invalid request.'], 400);
    // }


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
