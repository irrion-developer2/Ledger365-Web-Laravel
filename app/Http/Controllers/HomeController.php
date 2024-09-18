<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\TallyItem;
use App\Models\TallyVoucher;
use App\Models\TallyGroup;
use Yajra\DataTables\DataTables;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherAccAllocationHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyCompany;
use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{ 
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $companyGuids = $this->reportService->companyData();
        // dd($companyGuids);
        
        $user = User::count();
        $role = auth()->user()->role;

        /* cashBankAmount */
        $cashBank = TallyGroup::whereIn('company_guid', $companyGuids)->where('name', 'Bank Accounts')->first();
        if ($cashBank) {
            $cashBankName = $cashBank->name;
        } else {
            $cashBankName = 'Bank Accounts';
        }
        // dd($cashBank);
        $cashBankledger = TallyLedger::where('parent', $cashBankName)->where('company_guid', $companyGuids)->get();
        // dd($cashBankledger);
        $guids = $cashBankledger->pluck('guid');
        // $cashBankAmountHead = TallyVoucherHead::whereIn('ledger_guid', $guids)->sum('amount');
        // $cashBankAmountAcc = TallyVoucherAccAllocationHead::whereIn('ledger_guid', $guids)->sum('amount');
        $cashBankAmountHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                        ->whereIn('tally_voucher_heads.ledger_guid', $guids)
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->sum('tally_voucher_heads.amount');

        $cashBankAmountAcc = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                            ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $guids)
                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                            ->sum('tally_voucher_acc_allocation_heads.amount');
        $cashBankAmount = $cashBankAmountHead + $cashBankAmountAcc;

        /* cashBankAmount */

        /* Inventory Amount */
        $stockItemVoucherBalance = $this->reportService->calculateStockValue();
        /* Inventory Amount */

         /* Payables */
        $payableCreditNote = $this->calculatePayableCreditNote();
         /* Payables */

        /* Sales Receipt chart */
        $chartData = $this->chartSaleReceipt();
        $chartSaleAmt = abs(array_sum($chartData['sales']));
        $chartReceiptAmt = abs(array_sum($chartData['receipts']));
        $lastMonthsTotal = $this->getLastMonthsTotal($chartData);
        /* Sales Receipt chart */

        /* pie chart */
        $pieChartData = $this->getPieChartData();
        $pieChartDataTotal = $pieChartData['total'];
        $pieChartDataOverall = $pieChartData['data'];
        /* pie chart */

        /* Cash Amount */
        $cash = TallyGroup::where('name', 'Cash-in-Hand')->whereIn('company_guid', $companyGuids)->first();
        if ($cash) {
            $cashName = $cash->name;
        } else {
            $cashName = 'Cash-in-Hand';
            // return response()->json(['No group found with the name "Cash-in-Hand"' => true]);
        }
        $cashledger = TallyLedger::where('parent', $cashName)->whereIn('company_guid', $companyGuids)->get();
        $guids = $cashledger->pluck('guid');
        $cashAmount = TallyVoucherHead::whereIn('ledger_guid', $guids)->sum('amount');
        /* Cash Amount */


        if ($role == 'SuperAdmin') {
            return view('dashboard', compact('user'));
        } elseif ($role == 'Users') {
            return view('users-dashboard', compact('user','cashBankAmount','cashAmount','stockItemVoucherBalance','payableCreditNote','chartSaleAmt','chartReceiptAmt','chartData','lastMonthsTotal','pieChartDataOverall','pieChartDataTotal'));
        }
        abort(403, 'Unauthorized action.');
    }


    public function getPieChartData()
    {
        $companyGuids = $this->reportService->companyData();
        // Execute the query and get the data
        $pieChartData = DB::table('tally_ledgers as tl')
            ->leftJoin('tally_voucher_heads as tvh', 'tl.guid', '=', 'tvh.ledger_guid')
            ->select('tl.language_name', DB::raw('COALESCE(SUM(tvh.amount), 0) AS total_amount'))
            ->where('tl.parent', 'Sundry Debtors')
            ->whereIn('tl.company_guid', $companyGuids)
            ->groupBy('tl.language_name')
            ->pluck('total_amount', 'language_name');

        // Convert to array
        $pieChartDataArray = $pieChartData->toArray();

        // Calculate the total sum of amounts
        $totalAmount = array_sum(array_map('abs', $pieChartDataArray));

        // Return both the array and total amount
        return [
            'data' => $pieChartDataArray,
            'total' => $totalAmount
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

    private function chartSaleReceipt()
    {
        $companyGuids = $this->reportService->companyData();

        $salesData = [];
        $receiptData = [];

        for ($month = 4; $month <= 12; $month++) {
            $monthName = DateTime::createFromFormat('!m', $month)->format('F');

            // Fetch sales data for the month
            $ledgerSalesData = TallyVoucher::where('voucher_type', 'Sales')
                ->whereIn('company_guid', $companyGuids)
                ->whereMonth('voucher_date', $month)
                ->get(['id', 'ledger_guid']); // Get the collection of sales vouchers

            $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerSalesData->pluck('ledger_guid'))
                ->whereIn('tally_voucher_id', $ledgerSalesData->pluck('id'))
                ->sum('amount');

            // Fetch receipt data for the month
            $ledgerReceiptData = TallyVoucher::where('voucher_type', 'Receipt')
                ->whereIn('company_guid', $companyGuids)
                ->whereMonth('voucher_date', $month)
                ->get(['id', 'ledger_guid']); // Get the collection of receipt vouchers

            $totalReceipts = TallyVoucherHead::whereIn('ledger_guid', $ledgerReceiptData->pluck('ledger_guid'))
                ->whereIn('tally_voucher_id', $ledgerReceiptData->pluck('id'))
                ->sum('amount');

            // Store the totals in the arrays for each month
            $salesData[$monthName] = $totalSales;
            $receiptData[$monthName] = $totalReceipts;
        }

        return [
            'sales' => $salesData,
            'receipts' => $receiptData,
        ];
    }

    private function calculatePayableCreditNote()
    {

        $companyGuids = $this->reportService->companyData();

        $ledgerIds = TallyVoucher::where('voucher_type', 'credit note')
                        ->whereIn('company_guid', $companyGuids)
                        ->pluck('ledger_guid');
    
        $CreditAmount = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
            ->sum('amount');


        $DebitledgerIds = TallyVoucher::where('voucher_type', 'debit note')
                        ->whereIn('company_guid', $companyGuids)
                        ->pluck('ledger_guid');
    
        $DebitAmount = TallyVoucherHead::whereIn('ledger_guid', $DebitledgerIds)
            ->sum('amount');

        $PurchaseledgerIds = TallyVoucher::where('voucher_type', 'Purcahse')
                        ->whereIn('company_guid', $companyGuids)
                        ->pluck('ledger_guid');
    
        $PurcahseAmount = TallyVoucherHead::whereIn('ledger_guid', $PurchaseledgerIds)
            ->sum('amount');

            
        $SaleledgerIds = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereIn('company_guid', $companyGuids)
                        ->pluck('ledger_guid');
    
        $SaleAmount = TallyVoucherHead::whereIn('ledger_guid', $SaleledgerIds)
            ->sum('amount');

            $total = $CreditAmount + $DebitAmount;

            
        return $total;
    }
    
    private function extractNumericValue($value)
    {
        // Remove non-numeric characters except for decimal points
        $numericValue = preg_replace('/[^\d.]/', '', $value);

        // Convert to float
        return (float) $numericValue;
    }


    private function calculateStockItemVoucherAmount()
    {
        $stockItemVoucherPurchaseAmount = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })->sum('amount');

            // dd($stockItemVoucherPurchaseAmount);

        return [
            'purchase_amt' => $stockItemVoucherPurchaseAmount
        ];
    }

    private function calStockItemBalance()
    {
        // Sum of billed quantities for 'Sales' vouchers
        $stockItemVoucherSaleItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Sales');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Purchase' vouchers
        $stockItemVoucherPurchaseItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Purchase');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Credit Note' vouchers
        $stockItemVoucherCreditNoteItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Credit Note');
            })->sum('billed_qty');

        // Sum of billed quantities for 'Debit Note' vouchers
        $stockItemVoucherDebitNoteItem = TallyVoucherItem::whereHas('tallyVoucher', function ($query) {
                $query->where('voucher_type', 'Debit Note');
            })->sum('billed_qty');

        // Calculate total stock item voucher balance
        $stockItemVoucherBalance = ($stockItemVoucherSaleItem - $stockItemVoucherCreditNoteItem) - ($stockItemVoucherPurchaseItem - $stockItemVoucherDebitNoteItem);

        // Optionally, you can return the purchase item billed_qty or use it elsewhere
        return [
            'balance' => $stockItemVoucherBalance,
            'purchase_qty' => $stockItemVoucherPurchaseItem
        ];
    }
    
}
