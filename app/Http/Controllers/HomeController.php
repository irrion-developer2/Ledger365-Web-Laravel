<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\TallyItem;
use App\Models\TallyVoucher;
use App\Models\TallyLedgerGroup;
use Yajra\DataTables\DataTables;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
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
        $companyIds = $this->reportService->companyData();

        // dd($companyIds);
        $user = User::count();
        $role = auth()->user()->role;

        /* cashBankAmount */
        $cashBank = TallyLedgerGroup::where('ledger_group_name', 'Bank Accounts')->whereIn('company_id', $companyIds)->first();
        $cashBankName = $cashBank ? $cashBank->ledger_group_name : 'Bank Accounts';
        $cashBankId = $cashBank ? $cashBank->ledger_group_id : null;
        
        $cashBankAmount = TallyVoucherHead::join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                        ->where('tally_ledgers.ledger_group_id', $cashBankId)
                        ->whereIn('tally_ledgers.company_id', $companyIds)
                        ->sum('tally_voucher_heads.amount');
        /* cashBankAmount */

        /* Inventory Amount */
        // $stockItemVoucherBalance = $this->reportService->calculateStockValue($companyIds);
        /* Inventory Amount */

         /* Payables */
         $payable = TallyLedgerGroup::where('ledger_group_name', 'Sundry Creditors')->whereIn('company_id', $companyIds)->first();
         $payableName = $payable ? $payable->ledger_group_name : 'Sundry Creditors';
         $payableId = $payable ? $payable->ledger_group_id : null;
 
         $payables = TallyVoucherHead::join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                    ->where('tally_ledgers.ledger_group_id', $payableId)
                    ->whereIn('tally_ledgers.company_id', $companyIds)
                    ->sum('tally_voucher_heads.amount');
         /* Payables */

        /* Sales Receipt chart */
        $startDate = now()->startOfMonth();
        $endDate = now();
        $chartData = $this->chartSaleReceipt($companyIds, $startDate, $endDate);
        $chartSaleAmt = abs(array_sum($chartData['sales']));
        $chartReceiptAmt = abs(array_sum($chartData['receipts']));
        $lastMonthsTotal = $this->getLastMonthsTotal($chartData);
        /* Sales Receipt chart */

        /* pie chart */
        $pieChartData = $this->getPieChartData($companyIds);
        $pieChartDataTotal = $pieChartData['total'];
        $pieChartDataOverall = $pieChartData['data'];
        /* pie chart */

        /* Cash Amount */
        $cashGroup = TallyLedgerGroup::where('ledger_group_name', 'Cash-in-Hand')->whereIn('company_id', $companyIds)->first();
        $cashName = $cashGroup ? $cashGroup->ledger_group_name : 'Cash-in-Hand';
        $cashGroupId = $cashGroup ? $cashGroup->ledger_group_id : null;

        $cashAmount = TallyVoucherHead::join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                        ->where('tally_ledgers.ledger_group_id', $cashGroupId)
                        ->whereIn('tally_ledgers.company_id', $companyIds)
                        ->sum('tally_voucher_heads.amount');
        /* Cash Amount */

        $numberOfCustomers = TallyLedger::where('parent', 'Sundry Debtors')->whereIn('company_id', $companyIds)->count();
       
        if ($role == 'Administrative') {
            return view('dashboard', compact('user'));
        } elseif ($role == 'Owner' || $role == 'Employee') {
            return view('users-dashboard', compact('user','cashBankAmount','cashAmount','payables','chartSaleAmt','chartReceiptAmt','chartData','lastMonthsTotal','pieChartDataOverall','pieChartDataTotal','numberOfCustomers'));
        }
        abort(403, 'Unauthorized action.');
    }

    public function getPieChartData($companyIds)
    {
        $pieChartData = DB::table('tally_ledgers as tl')
            ->leftJoin('tally_voucher_heads as tvh', 'tl.ledger_id', '=', 'tvh.ledger_id')
            ->select('tl.ledger_name', DB::raw('COALESCE(SUM(tvh.amount), 0) AS total_amount'))
            ->where('tl.parent', 'Sundry Debtors')
            ->whereIn('tl.company_id', $companyIds)
            ->groupBy('tl.ledger_name')
            ->pluck('total_amount', 'ledger_name');

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

    public function getFilteredData(Request $request)
    {
        $filter = $request->get('filter', 'this_year');
        $companyIds = $this->reportService->companyData();

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

        $chartData = $this->chartSaleReceipt($companyIds, $startDate, $endDate);

        return response()->json([
            'chartData' => $chartData,
            'salesTotal' => array_sum($chartData['sales']),
            'receiptsTotal' => array_sum($chartData['receipts'])
        ]);
    }

    private function chartSaleReceipt($companyIds, $startDate, $endDate)
    {
        $salesData = [];
        $receiptData = [];
    
        $period = \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate);
    
        foreach ($period as $date) {
            $monthName = $date->format('F');
            $month = $date->month;
    
            \Log::info("Querying data for month: $monthName, Company IDs: " . json_encode($companyIds));
    
            // Total Sales
            $totalSales = TallyVoucher::join('tally_voucher_heads', function($join) {
                                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id');
                            })
                            ->join('tally_voucher_types', function($join) {
                                $join->on('tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                     ->on('tally_vouchers.company_id', '=', 'tally_voucher_types.company_id');
                            })
                            ->where('tally_voucher_types.voucher_type_name', 'Sales')
                            ->whereIn('tally_vouchers.company_id', $companyIds)
                            ->whereMonth('tally_vouchers.voucher_date', $month)
                            ->whereYear('tally_vouchers.voucher_date', $date->year)
                            ->sum('tally_voucher_heads.amount');
    
            \Log::info("Total Sales for $monthName: $totalSales");
    
            // Total Receipts
            $totalReceipts = TallyVoucher::join('tally_voucher_heads', function($join) {
                                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id');
                            })
                            ->join('tally_voucher_types', function($join) {
                                $join->on('tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                     ->on('tally_vouchers.company_id', '=', 'tally_voucher_types.company_id');
                            })
                            ->where('tally_voucher_types.voucher_type_name', 'Receipt')
                            ->whereIn('tally_vouchers.company_id', $companyIds)
                            ->whereMonth('tally_vouchers.voucher_date', $month)
                            ->whereYear('tally_vouchers.voucher_date', $date->year)
                            ->sum('tally_voucher_heads.amount');
    
            \Log::info("Total Receipts for $monthName: $totalReceipts");
    
            // Store data in arrays
            $salesData[$monthName] = $totalSales;
            $receiptData[$monthName] = $totalReceipts;
        }
    
        return [
            'sales' => $salesData,
            'receipts' => $receiptData,
        ];
    }
    
}
