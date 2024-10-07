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
        $user = User::count();
        $role = auth()->user()->role;

        /* cashBankAmount */
        $cashBank = TallyGroup::whereIn('company_guid', $companyGuids)
                    ->where('name', 'Bank Accounts')
                    ->first();

        $cashBankName = $cashBank ? $cashBank->name : 'Bank Accounts';

        $cashBankLedgerIds = TallyLedger::where('parent', $cashBankName)
                            ->whereIn('company_guid', $companyGuids)
                            ->pluck('guid');

        $cashBankAmountHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
        ->whereIn('tally_voucher_heads.ledger_guid', $cashBankLedgerIds)
        ->whereIn('tally_vouchers.company_guid', $companyGuids)
        ->sum('tally_voucher_heads.amount');

        $cashBankAmountAcc = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
        ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $cashBankLedgerIds)
        ->whereIn('tally_vouchers.company_guid', $companyGuids)
        ->sum('tally_voucher_acc_allocation_heads.amount');

        $cashBankAmount = $cashBankAmountHead + $cashBankAmountAcc;
        /* cashBankAmount */

        /* Inventory Amount */
        // $stockItemVoucherBalance = $this->reportService->calculateStockValue($companyGuids);
        /* Inventory Amount */

         /* Payables */
        $payableCreditNote = $this->calculatePayableCreditNote($companyGuids);
         /* Payables */

        /* Sales Receipt chart */
        $startDate = now()->startOfMonth();
        $endDate = now();
        $chartData = $this->chartSaleReceipt($companyGuids, $startDate, $endDate);
        $chartSaleAmt = abs(array_sum($chartData['sales']));
        $chartReceiptAmt = abs(array_sum($chartData['receipts']));
        $lastMonthsTotal = $this->getLastMonthsTotal($chartData);

        // dd($chartData, $chartSaleAmt, $chartReceiptAmt);
        /* Sales Receipt chart */

        /* pie chart */
        $pieChartData = $this->getPieChartData($companyGuids);
        $pieChartDataTotal = $pieChartData['total'];
        $pieChartDataOverall = $pieChartData['data'];
        /* pie chart */

        /* Cash Amount */
        $cashGroup = TallyGroup::where('name', 'Cash-in-Hand')->whereIn('company_guid', $companyGuids)->first();
        $cashName = $cashGroup ? $cashGroup->name : 'Cash-in-Hand';

        $cashAmount = TallyVoucherHead::whereIn('ledger_guid', function($query) use ($cashName, $companyGuids) {
            $query->select('guid')
                ->from('tally_ledgers')
                ->where('parent', $cashName)
                ->whereIn('company_guid', $companyGuids);
        })->sum('amount');
        /* Cash Amount */


        if ($role == 'SuperAdmin') {
            return view('dashboard', compact('user'));
        } elseif ($role == 'Users') {
            return view('users-dashboard', compact('user','cashBankAmount','cashAmount','payableCreditNote','chartSaleAmt','chartReceiptAmt','chartData','lastMonthsTotal','pieChartDataOverall','pieChartDataTotal'));
        }
        abort(403, 'Unauthorized action.');
    }

    public function getPieChartData($companyGuids)
    {
        $pieChartData = DB::table('tally_ledgers as tl')
            ->leftJoin('tally_voucher_heads as tvh', 'tl.guid', '=', 'tvh.ledger_guid')
            ->select('tl.language_name', DB::raw('COALESCE(SUM(tvh.amount), 0) AS total_amount'))
            ->where('tl.parent', 'Sundry Debtors')
            ->whereIn('tl.company_guid', $companyGuids)
            ->groupBy('tl.language_name')
            ->pluck('total_amount', 'language_name');

        $pieChartDataArray = $pieChartData->toArray();

        $totalAmount = array_sum(array_map('abs', $pieChartDataArray));
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

    private function calculatePayableCreditNote($companyGuids)
    {
        $CreditAmount = TallyVoucher::join('tally_voucher_heads', 'tally_voucher_heads.ledger_guid', '=', 'tally_vouchers.ledger_guid')
                ->where('tally_vouchers.voucher_type', 'credit note')
                ->whereIn('tally_vouchers.company_guid', $companyGuids)
                ->sum('tally_voucher_heads.amount');

        $DebitAmount = TallyVoucher::join('tally_voucher_heads', 'tally_voucher_heads.ledger_guid', '=', 'tally_vouchers.ledger_guid')
                ->where('tally_vouchers.voucher_type', 'debit note')
                ->whereIn('tally_vouchers.company_guid', $companyGuids)
                ->sum('tally_voucher_heads.amount');

        $total = $CreditAmount + $DebitAmount;

        return $total;
    }

    public function getFilteredData(Request $request)
    {
        $filter = $request->get('filter', 'this_year');
        $companyGuids = $this->reportService->companyData();

        switch ($filter) {
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $startDate = now()->firstOfQuarter();
                $endDate = now();
                break;
            case 'prev_quarter':
                $startDate = now()->subQuarter()->firstOfQuarter();
                $endDate = now()->subQuarter()->endOfQuarter();
                break;
            case 'prev_year':
                $startDate = now()->subYear()->startOfYear();
                $endDate = now()->subYear()->endOfYear();
                break;
            case 'this_year':
            default:
                $startDate = now()->startOfYear();
                $endDate = now();
                break;
        }

        $chartData = $this->chartSaleReceipt($companyGuids, $startDate, $endDate);

        return response()->json([
            'chartData' => $chartData,
            'salesTotal' => array_sum($chartData['sales']),
            'receiptsTotal' => array_sum($chartData['receipts'])
        ]);
    }

    private function chartSaleReceipt($companyGuids, $startDate, $endDate)
    {
        $salesData = [];
        $receiptData = [];

        $period = \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate);

        foreach ($period as $date) {
            $monthName = $date->format('F');
            $month = $date->month;

            \Log::info("Querying data for month: $monthName, Company GUIDs: " . json_encode($companyGuids));

            $totalSales = TallyVoucher::join('tally_voucher_heads', function($join) {
                    $join->on('tally_voucher_heads.ledger_guid', '=', 'tally_vouchers.ledger_guid')
                         ->on('tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id');
                })
                ->where('tally_vouchers.voucher_type', 'Sales')
                ->whereIn('tally_vouchers.company_guid', $companyGuids)
                ->whereMonth('tally_vouchers.voucher_date', $month)
                ->whereYear('tally_vouchers.voucher_date', $date->year)
                ->sum('tally_voucher_heads.amount');

            \Log::info("Total Sales for $monthName: $totalSales");

            $totalReceipts = TallyVoucher::join('tally_voucher_heads', function($join) {
                    $join->on('tally_voucher_heads.ledger_guid', '=', 'tally_vouchers.ledger_guid')
                         ->on('tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id');
                })
                ->where('tally_vouchers.voucher_type', 'Receipt')
                ->whereIn('tally_vouchers.company_guid', $companyGuids)
                ->whereMonth('tally_vouchers.voucher_date', $month)
                ->whereYear('tally_vouchers.voucher_date', $date->year) // Ensure the year matches
                ->sum('tally_voucher_heads.amount');

            \Log::info("Total Receipts for $monthName: $totalReceipts");

            $salesData[$monthName] = $totalSales;
            $receiptData[$monthName] = $totalReceipts;
        }

        return [
            'sales' => $salesData,
            'receipts' => $receiptData,
        ];
    }
}
