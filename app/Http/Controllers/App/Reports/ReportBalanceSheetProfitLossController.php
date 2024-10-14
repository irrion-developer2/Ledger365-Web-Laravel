<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyGroup;
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
        $companyGuids = $this->reportService->companyData();

        $company = TallyCompany::where('guid', $companyGuids)->first();

        return view ('app.reports.balanceSheet.profitLoss.index', compact('company'));
    }

    public function OpeningGetData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $openingValueSum = TallyItem::whereIn('company_guid', $companyGuids)->sum('opening_value');
            $data = [
                [
                    'name' => 'Opening Stock',
                    'opening_value' => number_format(abs($openingValueSum), 3)
                ]
            ];
            return response()->json(['data' => $data]);
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
    
            $Balancequery = TallyGroup::where(function($query) {
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

                    $groupLedgerIdsQuery = TallyGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
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
    
                    return number_format(abs($totalAmount), 3);
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
                // Assuming opening_balance and opening_value are now decimals
                // $openingBalance = $entry->opening_balance ?? 0;
                // $openingValue = $entry->opening_value ?? 0;

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
                    $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');

                    // Use the correct variable for the stock on hand balance
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherBalance;
                    $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;

                    // Accumulate the stock value
                    $stock_value += $stockOnHandValue;
                }
            }

            // Return the response with calculated stock value
            $data = [
                [
                    'name' => 'Closing Stock',
                    'closing_value' => number_format($stock_value, 3)
                ]
            ];

            $endTime = microtime(true);

            \Log::info('getClosingStockData execution time: ' . ($endTime - $startTime) . ' seconds.');

            return response()->json(['data' => $data]);
        }
    }


    // public function getClosingStockData(Request $request)
    // {
    //     $startTime = microtime(true);
    
    //     $companyGuids = $this->reportService->companyData();
    
    //     if ($request->ajax()) {
    //         $tallyItems = TallyItem::whereIn('company_guid', $companyGuids)
    //                     ->select('id', 'guid', 'name', 'opening_balance', 'opening_value', 'company_guid') 
    //                     ->get(); 
    
    //         $stock_value = 0; // Initialize stock_value
    //         $closingValueSum = 0;
    
    //         foreach ($tallyItems as $entry) {
    //             $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
    //             $openingValue = $this->reportService->extractNumericValue($entry->opening_value);
    
    //             // Calculate the stock item voucher balances
    //             $stockItemVoucherSaleBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Sales');
    //                 })->sum('billed_qty');
    
    //             $stockItemVoucherDebitNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Debit Note');
    //                 })->sum('billed_qty');
    
    //             $stockItemVoucherPurchaseBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Purchase');
    //                 })->sum('billed_qty');
    
    //             $stockItemVoucherCreditNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Credit Note');
    //                 })->sum('billed_qty');
    
    //             // Calculate the balance of stock on hand
    //             $stockItemVoucherBalance = ($stockItemVoucherSaleBalance - $stockItemVoucherCreditNoteBalance) 
    //                                      - ($stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance);
    
    //             // Calculate amounts
    //             $stockItemVoucherPurchaseAmount = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Purchase');
    //                 })->sum('amount');
    
    //             $stockItemVoucherDebitNoteAmount = TallyVoucherItem::where('stock_item_guid', $entry->guid)
    //                 ->whereHas('tallyVoucher', function ($query) {
    //                     $query->where('voucher_type', 'Debit Note');
    //                 })->sum('amount');
    
    //             $openingAmount = ($stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount);
    
    //             $finalOpeningValue = $openingValue - $openingAmount;
    //             $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;
    
    //             if ($finalOpeningBalance != 0) {
    //                 $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
    //                 $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
                    
    //                 // Use the correct variable for the stock on hand balance
    //                 $stockOnHandBalance = $openingBalance - $stockItemVoucherBalance;
    //                 $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
    
    //                 // Accumulate the stock value
    //                 $stock_value += $stockOnHandValue;
    //             }
    //         }
    
    //         // Return the response with calculated stock value
    //         $data = [
    //             [
    //                 'name' => 'Closing Stock',
    //                 'closing_value' => number_format($stock_value, 3)
    //             ]
    //         ];
    
    //         $endTime = microtime(true);
    
    //         \Log::info('getClosingStockData execution time: ' . ($endTime - $startTime) . ' seconds.');
    
    //         return response()->json(['data' => $data]);
    //     }
    // }
    
    

    
    // public function getClosingStockData(Request $request)
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     if ($request->ajax()) {
    //         $tallyItems = TallyItem::with('tallyVoucherItems')->whereIn('company_guid', $companyGuids)->get();
    //         $closingValueSum = 0;


    //         foreach ($tallyItems as $entry) {
    //             $stockOnHandBalance = 0;
    //             $openingBalance = 0;
    //             $stockOnHandValue = 0;
            
    //             $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
    //             $openingValue = $this->reportService->extractNumericValue($entry->opening_value);
            
    //             $stockItemData = $this->reportService->calculateStockItemVoucherBalance($entry->name);
    //             $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
    //             $stockItemVoucherDebitNoteBalance = $stockItemData['debit_note_qty'];
    //             $stockItemVoucherHandBalance = $stockItemData['balance'];
            
    //             $stockAmountData = $this->reportService->calculateStockItemVoucherAmount($entry->name);
    //             $stockItemVoucherPurchaseAmount = $stockAmountData['purchase_amt'];
    //             $stockItemVoucherDebitNoteAmount = $stockAmountData['debit_note_amt'];
            
    //             $openingAmount = $stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount;
    //             $finalOpeningValue = $openingValue - $openingAmount;
    //             $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;
            
    //             if ($finalOpeningBalance != 0) {
    //                 $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
    //                 $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
    //             } else {
    //                 // Handle division by zero here, e.g., set a default value or skip this item
    //                 $stockItemVoucherSaleValue = 0;  // or any other default value
    //             }
            
    //             $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
    //             $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
    //             $closingValueSum += $stockOnHandValue;
    //         }
            
    //         $data = [
    //             [
    //                 'name' => 'Closing Stock',
    //                 'closing_value' => $this->reportService->calculateStockValue()
    //             ]
    //          ];

    //         return response()->json(['data' => $data]);
    //     }
    // }

}
