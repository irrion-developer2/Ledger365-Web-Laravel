<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use App\Models\TallyItem;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use App\Models\TallyVoucherHead;
use App\Models\TallyLedger;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;
use App\DataTables\SuperAdmin\StockItemDataTable;

class StockItemController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return View ('app.stock-items.index');
    }
    
    public function getData(Request $request)
    {

        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);
      

            $stockItems = TallyItem::with('tallyVoucherItems')->whereIn('company_guid', $companyGuids)
                ->select('id', 'guid', 'name', 'alias', 'parent', 'category','opening_balance', 'opening_value', 'company_guid');

                                // dd($stockItems);
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;

            Log::info('Total first db request execution time for StockItemController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($stockItems)
                ->addIndexColumn()

                ->addColumn('stockonhand_opening_balance', function ($entry) {
                    $openingBalance = trim($entry->opening_balance);

                    $numericPart = '';
                    $unitPart = '';

                    if (preg_match('/^([\d.,]+)\s*(.*)$/', $openingBalance, $matches)) {
                        $numericPart = $matches[1];
                        $unitPart = isset($matches[2]) ? $matches[2] : '';
                    } else {
                        // \Log::warning("Failed to match opening balance: $openingBalance");
                    }
                    $openingBalanceValue = (float) str_replace([',', ' '], '', $numericPart);

                    $unit = $entry->unit ?? $entry->pluck('unit')->filter()->first();


                    $stockItemVoucherSaleBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                    ->whereHas('tallyVoucher', function ($query) {
                        $query->where('voucher_type', 'Sales');
                    })->sum('billed_qty');
        
                    $stockItemVoucherPurchaseBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Purchase');
                        })->sum('billed_qty');
                    $stockItemVoucherCreditNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Credit Note');
                        })->sum('billed_qty');
            
                    $stockItemVoucherDebitNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Debit Note');
                        })->sum('billed_qty');

                    $stockItemVoucherBalance = ($stockItemVoucherSaleBalance - $stockItemVoucherCreditNoteBalance) - ($stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance);


                    $stockOnHandBalance = $openingBalanceValue - $stockItemVoucherBalance;

                    return $stockOnHandBalance . ' ' . $unit;
                })
                ->addColumn('stockonhand_opening_value', function ($entry) {
                    $stockOnHandBalance = 0;
                    $openingBalance = 0;
                    $stockOnHandValue = 0;

                    // Extract the opening balance and value
                    $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
                    $openingValue = $this->reportService->extractNumericValue($entry->opening_value);

                    $stockItemVoucherSaleBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                    ->whereHas('tallyVoucher', function ($query) {
                        $query->where('voucher_type', 'Sales');
                    })->sum('billed_qty');
        
                    $stockItemVoucherPurchaseBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Purchase');
                        })->sum('billed_qty');
                    $stockItemVoucherCreditNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Credit Note');
                        })->sum('billed_qty');
            
                    $stockItemVoucherDebitNoteBalance = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                        ->whereHas('tallyVoucher', function ($query) {
                            $query->where('voucher_type', 'Debit Note');
                        })->sum('billed_qty');

                    $stockItemVoucherHandBalance = ($stockItemVoucherSaleBalance - $stockItemVoucherCreditNoteBalance) - ($stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance);



                    $stockItemVoucherPurchaseAmount = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                    ->whereHas('tallyVoucher', function ($query) {
                        $query->where('voucher_type', 'Purchase');
                    })->sum('amount');
                    $stockItemVoucherDebitNoteAmount = TallyVoucherItem::where('stock_item_guid', $entry->guid)
                    ->whereHas('tallyVoucher', function ($query) {
                        $query->where('voucher_type', 'Debit Note');
                    })->sum('amount');

                    // Calculate opening amount and balances
                    $openingAmount = $stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount;
                    $finalOpeningValue = $openingValue - $openingAmount;
                    $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;

                    if ($finalOpeningBalance == 0) {
                        // Prevent division by zero by assigning a default value (e.g., 0 or a calculated fallback)
                        $stockItemVoucherSaleValue = 0;
                        $stockOnHandBalance = 0;
                    } else {
                        $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                        $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
                        $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                    }

                    // Calculate stock on hand value
                    $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;

                    return number_format($stockOnHandValue, 2);
                })
                ->make(true);

                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;

                Log::info('Total end execution time for StockItemController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

                return $dataTable;
        }
    }

    private function calculateStockItemVoucherBalance($stockItemName)
    {
        // Sum of billed quantities for 'Sales' vouchers
        $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Sales');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Purchase' vouchers
        $stockItemVoucherPurchaseItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Credit Note' vouchers
        $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Credit Note');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Debit Note' vouchers
        $stockItemVoucherDebitNoteItem = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Debit Note');
            })->sum('billed_qty');

        // Calculate total stock item voucher balance
        $stockItemVoucherBalance = ($stockItemVoucherSaleItem - $stockItemVoucherCreditNoteItem) - ($stockItemVoucherPurchaseItem - $stockItemVoucherDebitNoteItem);

        // Optionally, you can return the purchase item billed_qty or use it elsewhere
        return [
            'balance' => $stockItemVoucherBalance,
            'purchase_qty' => $stockItemVoucherPurchaseItem,
            'debit_note_qty' => $stockItemVoucherDebitNoteItem
        ];
    }

    private function calculateStockItemVoucherAmount($stockItemName)
    {
        // Fetch the amount and the earliest voucher date for 'Purchase' vouchers
        $purchaseVoucherData = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })
            ->selectRaw('SUM(amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date')
            ->join('tally_vouchers', 'tally_voucher_items.tally_voucher_id', '=', 'tally_vouchers.id')
            ->first();

        // Fetch the amount and the earliest voucher date for 'Debit Note' vouchers
        $debitNoteVoucherData = TallyVoucherItem::where('stock_item_name', $stockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Debit Note');
            })
            ->selectRaw('SUM(amount) as total_amount, MIN(tally_vouchers.voucher_date) as voucher_date')
            ->join('tally_vouchers', 'tally_voucher_items.tally_voucher_id', '=', 'tally_vouchers.id')
            ->first();

        // Prepare the result
        return [
            'purchase_amt' => $purchaseVoucherData->total_amount ?? 0,
            'purchase_date' => $purchaseVoucherData->voucher_date ?? null,
            'debit_note_amt' => $debitNoteVoucherData->total_amount ?? 0,
            'debit_note_date' => $debitNoteVoucherData->voucher_date ?? null,
        ];
    }

    private function extractNumericValue($value)
    {
        $numericValue = preg_replace('/[^\d.]/', '', $value);
        return (float) $numericValue;
    }

    public function AllStockItemReports($stockItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $stockItem = TallyItem::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($stockItemId);
        // dd($stockItem);
        $stockItemVoucherItem = TallyVoucherItem::where('stock_item_name', $stockItem->name)->get();

        //credit note
        $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $stockItem->name)
        ->whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type', 'Credit Note');
        })
        ->get();

        //debit note
        $stockItemVoucherDebitNoteItem = TallyVoucherItem::where('stock_item_name', $stockItem->name)
        ->whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type', 'Debit Note');
        })
        ->get();

        $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $stockItem->name)
        ->whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type', 'Sales');
        })
        ->get();

        $stockItemVoucherSaleItemBill = $stockItemVoucherSaleItem->sum('billed_qty');

        $stockItemVoucherSaleItemConnect = [];
        foreach ($stockItemVoucherSaleItem as $stockItemVoucher) {
            $id = $stockItemVoucher->tally_voucher_id;

            $tallyVouchers = TallyVoucher::where('id', $id)->whereIn('company_guid', $companyGuids)->get();
            if ($tallyVouchers->isNotEmpty()) {
                $stockItemVoucherSaleItemConnect[] = [
                    'tally_voucher_items' => $stockItemVoucher,
                    'tally_vouchers' => $tallyVouchers,
                ];
            }
        }


        $stockItemVoucherPurchaseItem = TallyVoucherItem::where('stock_item_name', $stockItem->name)
        ->whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type', 'Purchase');
        })
        ->get();

        $stockItemVoucherPurchaseItemConnect = [];
        foreach ($stockItemVoucherPurchaseItem as $stockItemVoucher) {
            $id = $stockItemVoucher->tally_voucher_id;

            $tallyVouchers = TallyVoucher::where('id', $id)->whereIn('company_guid', $companyGuids)->get();
            if ($tallyVouchers->isNotEmpty()) {
                $stockItemVoucherPurchaseItemConnect[] = [
                    'tally_voucher_items' => $stockItemVoucher,
                    'tally_vouchers' => $tallyVouchers,
                ];
            }
        }

        $stockOnHandBalance = 0;
        $openingBalance = 0;
        $stockOnHandValue = 0;
        $openingBalance = $this->extractNumericValue($stockItem->opening_balance);
        $openingValue = $this->extractNumericValue($stockItem->opening_value);


        $stockItemVoucherSaleBalance =  $stockItemVoucherSaleItem->sum('billed_qty');
        $stockItemVoucherPurchaseBalance =  $stockItemVoucherPurchaseItem->sum('billed_qty');
        $stockItemVoucherCreditNoteBalance =  $stockItemVoucherCreditNoteItem->sum('billed_qty');
        $stockItemVoucherDebitNoteBalance =  $stockItemVoucherDebitNoteItem->sum('billed_qty');
        $stockItemVoucherBalance = ($stockItemVoucherSaleBalance - $stockItemVoucherCreditNoteBalance) - ($stockItemVoucherPurchaseBalance  - $stockItemVoucherDebitNoteBalance);


        $stockItemVoucherSaleAmount =  $stockItemVoucherSaleItem->sum('amount');
        $stockItemVoucherPurchaseAmount =  $stockItemVoucherPurchaseItem->sum('amount');
        $stockItemVoucherCreditNoteAmount =  $stockItemVoucherCreditNoteItem->sum('amount');
        $stockItemVoucherDebitNoteAmount =  $stockItemVoucherDebitNoteItem->sum('amount');

        $stockItemVoucherAmount = $stockItemVoucherSaleAmount + $stockItemVoucherPurchaseAmount + $stockItemVoucherCreditNoteAmount + $stockItemVoucherDebitNoteAmount;

        $openingAmount = ($stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount);

        $openingAmountSale = ($stockItemVoucherSaleAmount + $stockItemVoucherCreditNoteAmount);

        $finalOpeningValue = $openingValue - $openingAmount;
        // $finalOpeningValue = $openingValue - $stockItemVoucherPurchaseAmount;
        $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;

        // dd($finalOpeningValue, $openingAmount, $openingAmountSale, $finalOpeningBalance, $stockItemVoucherBalance);

        if ($openingBalance == 0) {
            $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
            $stockOnHandBalance = $openingBalance - $stockItemVoucherBalance;
        } else {
            $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
            $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
            $stockOnHandBalance = $openingBalance - $stockItemVoucherBalance;
        }

        $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;


        // dd($stockOnHandValue, $stockItemVoucherSaleValue, $stockOnHandBalance,);

        $stockItemVoucherSaleHead = [];
        foreach ($stockItemVoucherSaleItem as $stockItemVoucher) {
            $id = $stockItemVoucher->tally_voucher_id;

            $tallyVoucherHeads = TallyVoucherHead::where('tally_voucher_id', $id)->get();
            if ($tallyVoucherHeads->isNotEmpty()) {
                $gstRates = [];

                foreach ($tallyVoucherHeads as $tallyVoucherHead) {
                    // Check if the ledger_name contains specific GST-related keywords
                    if (preg_match('/(SGST|CGST|IGST) @(\d+)%/', $tallyVoucherHead->ledger_name, $matches)) {
                        // Extract the percentage part and convert it to a float
                        $rate = floatval($matches[2]);
                        $gstRates[] = $rate;
                    }
                }

                // Combine and format the GST rates, if any
                if (!empty($gstRates)) {
                    $totalGstRate = array_sum($gstRates);
                    $formattedGstRate = $totalGstRate . '%';
                } else {
                    $formattedGstRate = null;
                }

                $stockItemVoucherSaleHead[] = [
                    'tally_voucher_items' => $stockItemVoucher,
                    'tally_voucher_heads' => $tallyVoucherHeads,
                    'gst_rate' => $formattedGstRate, // Add GST rate to the output
                ];
            }
        }

        $menuItems = TallyItem::whereIn('company_guid', $companyGuids)->get();
        $totalCount = TallyItem::whereIn('company_guid', $companyGuids)->count();

        return view('app.stock-items._stock_item_list', [
            'stockItem' => $stockItem,
            'stockItemId' => $stockItemId ,
            'menuItems' => $menuItems,
            'totalCount' => $totalCount,
            'stockItemVoucherItem' => $stockItemVoucherItem,
            'stockItemVoucherSaleItem' => $stockItemVoucherSaleItem,
            'stockItemVoucherSaleItemConnect' => $stockItemVoucherSaleItemConnect,
            'stockItemVoucherPurchaseItem' => $stockItemVoucherPurchaseItem,
            'stockItemVoucherPurchaseItemConnect' => $stockItemVoucherPurchaseItemConnect,
            'stockOnHandBalance' => $stockOnHandBalance,
            'stockOnHandValue' => $stockOnHandValue,
            'stockItemVoucherSaleHead' =>$stockItemVoucherSaleHead
        ]);
    }


    public function AllSaleStockItemReports($saleStockItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleStockItem = TallyItem::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleStockItemId);

        $stockItemVoucherItem = TallyVoucherItem::where('stock_item_name', $saleStockItem->name)->get();

        $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $saleStockItem->name)
        ->whereHas('tallyVoucher', function ($query) {
            $query->where('voucher_type', 'Sales');
        })
        ->get();

        $menuItems = TallyItem::whereIn('company_guid', $companyGuids)->get();

        return view('app.stock-items._sale_stock_item_list', [
            'saleStockItem' => $saleStockItem,
            'saleStockItemId' => $saleStockItemId ,
            'menuItems' => $menuItems,
        ]);
    }

    public function getSaleStockItemData($saleStockItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleStockItem = TallyItem::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleStockItemId);

        $saleStockItemName = $saleStockItem->name;

        $saleStockVoucherItems = TallyVoucherItem::where('stock_item_name', $saleStockItemName)
            ->whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Sales');
            })
            ->get();

        $partyLedgerNames = $saleStockVoucherItems->flatMap(function ($voucherItem) {
            return TallyVoucher::where('id', $voucherItem->tally_voucher_id)
                ->pluck('party_ledger_name');
        })->unique()->toArray();

        $tallyLedgers = TallyLedger::whereIn('language_name', $partyLedgerNames)->whereIn('company_guid', $companyGuids)->get();

        $ledgerGuids = $tallyLedgers->pluck('guid');

        $query = TallyLedger::select(
                'tally_ledgers.language_name',
                'tally_ledgers.guid',
                'tally_vouchers.voucher_number',
                'tally_voucher_items.amount',
                'tally_voucher_items.stock_item_name',
                'tally_voucher_items.billed_qty',
                'tally_voucher_items.unit',
                'tally_voucher_items.rate'
            )
            ->leftJoin('tally_vouchers', 'tally_ledgers.language_name', '=', 'tally_vouchers.party_ledger_name')
            ->leftJoin('tally_voucher_items', 'tally_vouchers.id', '=', 'tally_voucher_items.tally_voucher_id')
            ->whereIn('tally_ledgers.guid', $ledgerGuids)
            ->where('tally_vouchers.voucher_type', 'Sales')
            ->where('tally_voucher_items.stock_item_name', $saleStockItemName)
            ->whereIn('tally_ledgers.company_guid', $companyGuids)
            ->groupBy('tally_ledgers.language_name', 'tally_ledgers.guid', 'tally_vouchers.voucher_number', 'tally_voucher_items.amount', 'tally_voucher_items.stock_item_name', 'tally_voucher_items.billed_qty', 'tally_voucher_items.unit', 'tally_voucher_items.rate');

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

}
