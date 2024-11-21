<?php

namespace App\Http\Controllers\App;

use DateTime;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyItem;
use App\Models\TallyVoucher;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherAccAllocationHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyCompany;
use App\Services\ReportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnalyticController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $companyIds = $this->reportService->companyData();

        /* Sales Receipt chart */
        $chartData = $this->chartSaleReceipt($companyIds);
        $salesData = $chartData['sales'];
        $chartSaleAmt = abs(array_sum($chartData['sales']));
        // $chartReceiptAmt = abs(array_sum($chartData['receipts']));
        // $lastMonthsTotal = $this->getLastMonthsTotal($chartData);
        // /* Sales Receipt chart */

        // /* Avg Sales */
        $nonZeroSales = array_filter($salesData, function($value) {
            return $value != 0;
        });
        $totalSalesAmt = abs(array_sum($nonZeroSales));
        $monthsWithValueCount = count($nonZeroSales);

        $avg_sales = $monthsWithValueCount > 0 ? $totalSalesAmt / $monthsWithValueCount : 0;
        // $avg_sales = $th($averageSalesAmt);
        // /* Avg Sales */


        // /* pie chart */
        // $pieChartData = $this->getPieChartData($companyGuids);
        // $pieChartDataTotal = $pieChartData['total'];
        // $pieChartDataOverall = $pieChartData['data'];
        // /* pie chart */

        // /* No. Of Customers */
        $number_of_customers = TallyLedger::where('parent', 'Sundry Debtors')->whereIn('company_id', $companyIds)->count();
        // /* No. Of Customers */

        // /* Stock Value */
        $stock_value = $this->reportService->calculateStockValue();
        // /* Stock Value */

        // /* Top 5 Customers */
        // $topCustomers = TallyLedger::where('parent', 'Sundry Debtors')
        // ->whereIn('company_guid', $companyGuids)
        // ->with(['vouchers' => function ($query) {
        //     $query->where('voucher_type', 'Sales');
        // }])
        // ->get()
        // ->map(function ($ledger) {
        //     $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledger->vouchers->pluck('ledger_guid'))
        //         ->sum('amount');

        //     return [
        //         'name' => $ledger->name,
        //         'sales' => abs($totalSales)
        //     ];
        // })
        // ->sortByDesc('sales')
        // ->take(5);

        // /* Calculate max sales for top customers */
        // $maxSales = $topCustomers->max('sales');

        // /* Top 5 Customers */


        // /* Top 5 Stock */
        // $tallyItems = TallyItem::whereIn('company_guid', $companyGuids)->get();

        // $stockItems = $tallyItems->map(function ($entry) {
        //     $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
        //     $openingValue = $this->reportService->extractNumericValue($entry->opening_value);

        //     $stockItemData = $this->reportService->calculateStockItemVoucherBalance($entry->name);
        //     $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
        //     $stockItemVoucherHandBalance = $stockItemData['balance'];

        //     $stockAmountData = $this->reportService->calculateStockItemVoucherAmount($entry->name);
        //     $stockItemVoucherAmount = $stockAmountData['purchase_amt'];

        //     $finalOpeningValue = $openingValue - $stockItemVoucherAmount;
        //     $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance;

        //     $stockOnHandValue = 0; // Default to 0

        //     if ($finalOpeningBalance != 0) {
        //         $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
        //         $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
        //         $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
        //         $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
        //     }

        //     return [
        //         'name' => $entry->name,
        //         'stock_value' => $stockOnHandValue
        //     ];
        // });

        // $top5StockItems = $stockItems->sortByDesc('stock_value')->take(5);
        // $maxStockValue = $top5StockItems->max('stock_value');
        // /* Top 5 Stock */

        // /* Customer Category */
        // $customerCategory = TallyVoucher::where('voucher_type', 'Sales')
        //                     ->whereIn('company_guid', $companyGuids)
        //                     ->whereHas('ledger', function ($query) {
        //                         $query->where('parent', 'Sundry Debtors');
        //                     })
        //                     ->with(['voucherHeads' => function ($query) {
        //                         $query->select('ledger_guid', 'amount');
        //                     }, 'ledger'])
        //                     ->get()
        //                     ->groupBy('gst_registration_type')
        //                     ->map(function ($vouchers, $gstType) {
        //                         $totalSales = $vouchers->pluck('voucherHeads')->flatten()
        //                             ->sum(function ($voucherHead) {
        //                                 return abs($voucherHead->amount);
        //                             });

        //                         return [
        //                             'gst_registration_type' => $gstType,
        //                             'total_sales' => $totalSales,
        //                         ];
        //                     })->values();
        // /* Customer Category */

        // /* Closing Stock */
        // $ClosingStock = $this->reportService->calculateStockValue();

        // $closingStockData = $this->getMonthlyClosingStockData($companyGuids);
        /* Closing Stock */


        return view('app.analytics.index', [
            'chartSaleAmt' => $chartSaleAmt,
            // 'chartReceiptAmt' => $chartReceiptAmt,
            // 'lastMonthsTotal' => $lastMonthsTotal,
            // 'pieChartDataTotal' => $pieChartDataTotal,
            'chartData' => $chartData,
            // 'pieChartDataOverall' => $pieChartDataOverall,
            'number_of_customers' => $number_of_customers,
            'avg_sales' => $avg_sales,
            'stock_value' => $stock_value,
            // 'topCustomers' => $topCustomers,
            // 'maxSales' => $maxSales ,
            // 'top5StockItems' => $top5StockItems,
            // 'maxStockValue' => $maxStockValue,
            // 'customerCategory' => $customerCategory,
            // 'ClosingStock' => $ClosingStock,
            // 'closingStockData' => $closingStockData,
        ]);
    }

    private function getMonthlyClosingStockData($companyGuids)
    {
        $purchaseData = TallyVoucherItem::whereHas('tallyVoucher', function ($query) use ($companyGuids) {
                $query->where('voucher_type', 'Purchase')->whereIn('company_guid', $companyGuids);
            })
            ->selectRaw('SUM(ABS(amount)) as total_amount, DATE_FORMAT(tally_vouchers.voucher_date, "%Y-%m") as month') // Format month as "YYYY-MM"
            ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.id')
            ->groupBy('month')
            ->pluck('total_amount', 'month');

        $debitNoteData = TallyVoucherItem::whereHas('tallyVoucher', function ($query) use ($companyGuids) {
                $query->where('voucher_type', 'Debit Note')->whereIn('company_guid', $companyGuids);
            })
            ->selectRaw('SUM(ABS(amount)) as total_amount, DATE_FORMAT(tally_vouchers.voucher_date, "%Y-%m") as month') // Format month as "YYYY-MM"
            ->join('tally_vouchers', 'tally_voucher_items.voucher_head_id', '=', 'tally_vouchers.id')
            ->groupBy('month')
            ->pluck('total_amount', 'month');

        $combinedData = [];
        foreach ($purchaseData as $month => $amount) {
            $combinedData[$month] = $amount + ($debitNoteData[$month] ?? 0);
        }

        foreach ($debitNoteData as $month => $amount) {
            if (!isset($combinedData[$month])) {
                $combinedData[$month] = $amount;
            }
        }

        ksort($combinedData);

        $formattedData = [];
        foreach ($combinedData as $month => $amount) {
            $formattedMonth = \DateTime::createFromFormat('!Y-m', $month)->format('M Y');
            $formattedData[$formattedMonth] = $amount;
        }

        return $formattedData;
    }

    private function chartSaleReceipt($companyIds)
    { $companyIds = $this->reportService->companyData();
        $salesData = [];
        $receiptData = [];

        for ($month = 4; $month <= 12; $month++) {
            $monthName = DateTime::createFromFormat('!m', $month)->format('F');

            $totalSales = TallyVoucher::join('tally_voucher_heads', function ($join) {
                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                     ->on('tally_voucher_heads.ledger_id', '=', 'tally_vouchers.company_id');
            })
            ->where('tally_vouchers.voucher_type_id', 'Sales') 
            ->whereIn('tally_vouchers.company_id', $companyIds) 
            ->whereMonth('tally_vouchers.voucher_date', $month) 
            ->sum('tally_voucher_heads.amount');

            $totalReceipts = TallyVoucher::join('tally_voucher_heads', function ($join) {
                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                     ->on('tally_voucher_heads.ledger_id', '=', 'tally_vouchers.company_id');
            })
            ->where('tally_vouchers.voucher_type_id', 'Receipt') 
            ->whereIn('tally_vouchers.company_id', $companyIds) 
            ->whereMonth('tally_vouchers.voucher_date', $month) 
            ->sum('tally_voucher_heads.amount');
        

            $salesData[$monthName] = $totalSales;
            $receiptData[$monthName] = $totalReceipts;
        }

        return [
            'sales' => $salesData,
            'receipts' => $receiptData,
        ];
    }

    private function getLastMonthsTotal(array $chartData)
    {
        $currentDate = new \DateTime();
        $months = [];
        for ($i = 0; $i < 1; $i++) {
            $date = new \DateTime("first day of -$i month");
            $months[] = $date->format('F');
        }
        $receiptTotal = 0;
        $salesTotal = 0;

        foreach ($months as $month) {
            if (isset($chartData['receipts'][$month])) {
                $receiptTotal += $chartData['receipts'][$month];
            }
            if (isset($chartData['sales'][$month])) {
                $salesTotal += $chartData['sales'][$month];
            }
        }

        return [
            'sales' => $salesTotal,
            'receipts' => $receiptTotal,
        ];
    }

    public function getPieChartData($companyGuids)
    {
        $pieChartData = DB::table('tally_ledgers as tl')
            ->leftJoin('tally_voucher_heads as tvh', 'tl.guid', '=', 'tvh.ledger_guid')
            ->select('tl.name', DB::raw('COALESCE(SUM(tvh.amount), 0) AS total_amount'))
            ->where('tl.parent', 'Sundry Debtors')
            ->whereIn('tl.company_guid', $companyGuids)
            ->groupBy('tl.name')
            ->pluck('total_amount', 'name');

        $pieChartDataArray = $pieChartData->toArray();

        $totalAmount = array_sum(array_map('abs', $pieChartDataArray));
        return [
            'data' => $pieChartDataArray,
            'total' => $totalAmount
        ];
    }

    public function getAssetItemData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $tallyItem = TallyItem::with('tallyVoucherItems')
                                    ->whereIn('company_guid', $companyGuids);

            return DataTables::of($tallyItem)
                ->addIndexColumn()
                ->addColumn('amount', function ($entry) {
                    $stockOnHandBalance = 0;
                    $openingBalance = 0;
                    $stockOnHandValue = 0;

                    $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
                    $openingValue = $this->reportService->extractNumericValue($entry->opening_value);

                    $stockItemData = $this->reportService->calculateStockItemVoucherBalance($entry->name);
                    $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
                    $stockItemVoucherHandBalance = $stockItemData['balance'];

                    $stockAmountData = $this->reportService->calculateStockItemVoucherAmount($entry->name);
                    $stockItemVoucherAmount = $stockAmountData['purchase_amt'];

                    $finalOpeningValue = $openingValue - $stockItemVoucherAmount;
                    $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance;

                    if ($openingBalance == 0) {
                        $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                        $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                    } else {
                        $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                        $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
                        $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                    }
                    $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
                    return number_format($stockOnHandValue, 3);
                })

                ->make(true);
        }
    }

}
