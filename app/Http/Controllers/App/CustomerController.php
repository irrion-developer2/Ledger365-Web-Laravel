<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class CustomerController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.customers.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $customersQuery = TallyLedger::select(
                'tally_ledgers.company_id',
                'tally_ledgers.ledger_guid',
                'tally_ledgers.ledger_name',
                'tally_ledgers.party_gst_in'
            )
            ->where('tally_ledgers.parent', 'Sundry Debtors')
            ->whereIn('tally_ledgers.company_id', $companyIds)
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
            ->leftJoin('tally_vouchers', function ($join) {
                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                    ->where('tally_vouchers.is_cancelled', 0)
                    ->where('tally_vouchers.is_optional', 0);
            })
            ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id THEN tally_voucher_heads.amount END), 0) as total_sales')
            ->selectRaw('SUM(tally_voucher_heads.amount) as outstanding')
            ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Receipt" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id THEN tally_voucher_heads.amount END), 0) as payment_collection')
            ->groupBy('tally_ledgers.ledger_id');
        
        
            Log::info("Customer Query");        
            Log::info($this->reportService->getFinalQuery($customersQuery));

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
            if ($startDate && $endDate) {
                $customersQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }
    
            $customers = $customersQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('sales', function ($data) {
                    $totalSales = $data->total_sales;
                    return indian_format(abs($totalSales));
                })
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(abs($outstanding));
                })
                ->addColumn('payment_collection', function ($data) {
                    $payment_collection = $data->payment_collection;
                    return indian_format(abs($payment_collection));
                })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }
    
    public function otherLedgers()
    {
        return View('app.customers.ledger');
    }
    
    public function ledgergetData(Request $request)
    {
        $companyIds = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $customersQuery = TallyLedger::select(
                'tally_ledgers.company_id',
                'tally_ledgers.ledger_guid',
                'tally_ledgers.ledger_name',
                'tally_ledgers.party_gst_in'
            )
            ->where('tally_ledgers.parent', 'Sundry Creditors')
            ->whereIn('tally_ledgers.company_id', $companyIds)
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
            ->leftJoin('tally_vouchers', function ($join) {
                $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                    ->where('tally_vouchers.is_cancelled', 0)
                    ->where('tally_vouchers.is_optional', 0);
            })
            ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id THEN tally_voucher_heads.amount END), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id THEN tally_voucher_heads.amount END), 0) as outstanding')
            ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Receipt" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id THEN tally_voucher_heads.amount END), 0) as payment_collection')
            ->groupBy('tally_ledgers.ledger_id');
        
        
            Log::info("Customer Query");        
            Log::info($this->reportService->getFinalQuery($customersQuery));

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
            if ($startDate && $endDate) {
                $customersQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }
    
            $customers = $customersQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('sales', function ($data) {
                    $totalSales = $data->total_sales;
                    return indian_format(abs($totalSales));
                })
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(abs($outstanding));
                })
                ->addColumn('payment_collection', function ($data) {
                    $payment_collection = $data->payment_collection;
                    return indian_format(abs($payment_collection));
                })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

    public function show($customer)
    {
        $companyIds = $this->reportService->companyData();

        $ledger = TallyLedger::where('ledger_guid', $customer)
                                ->whereIn('company_id', $companyIds)
                                ->firstOrFail();

        return view('app.customers._customers-view', compact('ledger'));
    }

    public function getVoucherEntries($customer, Request $request)
    {
        $startTime = microtime(true);
        \DB::enableQueryLog();
        $companyIds = $this->reportService->companyData();
        
        $ledger = TallyLedger::where('ledger_guid', $customer)
            ->whereIn('company_id', $companyIds)
            ->firstOrFail();

        $ledgerId = $ledger->ledger_id;
        
        $voucherHeads = TallyVoucherHead::where('tally_voucher_heads.ledger_id', $ledgerId)
                        ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                        ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                        ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                        ->where(function($query) {
                            $query->whereNull('tally_vouchers.is_optional')
                                ->orWhere('tally_vouchers.is_optional', false);
                        })
                        ->orderBy('tally_vouchers.voucher_date', 'asc')
                        ->select([
                            'tally_vouchers.voucher_date',
                            'tally_vouchers.voucher_number',
                            'tally_vouchers.voucher_id',
                            'tally_voucher_heads.entry_type',
                            'tally_voucher_heads.amount',
                            'tally_voucher_types.voucher_type_name',
                            DB::raw("(SELECT GROUP_CONCAT(DISTINCT l2.ledger_name SEPARATOR ', ')
                                    FROM tally_voucher_heads vh2
                                    JOIN tally_ledgers l2 ON vh2.ledger_id = l2.ledger_id
                                    JOIN tally_ledger_groups lg2 ON l2.ledger_group_id = lg2.ledger_group_id
                                    WHERE vh2.voucher_id = tally_voucher_heads.voucher_id
                                    AND vh2.ledger_id != tally_voucher_heads.ledger_id
                                    AND lg2.ledger_group_name NOT IN ('Sundry Debtors', 'Sundry Creditors', 'Duties & Taxes')
                                    ) AS counterpart_ledger_name")
                        ])
                        ->get();

        \Log::info('Query Log: ', \DB::getQueryLog());

        $runningBalance = 0;
        $openingBalanceAdded = false;

        $groupedVouchers = $voucherHeads->groupBy('voucher_number')->map(function ($entries) use (&$runningBalance, &$openingBalanceAdded, $ledger) {
            $totalAmount = $entries->sum('amount');

            $openingBalance = floatval($ledger->opening_balance ?? 0);
            if (!$openingBalanceAdded) {
                $runningBalance += $openingBalance;
                $openingBalanceAdded = true; 
            }
            $runningBalance += $totalAmount;

            return [
                'voucher_number' => $entries->first()->voucher_number,
                'voucher_id' => $entries->first()->voucher_id,
                'amount' => $totalAmount,
                'running_balance' => ($runningBalance == 0 || empty($runningBalance)) ? '0.00' : indian_format($runningBalance),
                'voucher_date' => $entries->first()->voucher_date,
                'voucher_type_name' => $entries->first()->voucher_type_name,
                'ledger_name' =>$entries->first()->counterpart_ledger_name, 
                'entry_type' => $entries->first()->entry_type
            ];
        })->values();

        // Apply date filters
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
        
        if ($startDate && $endDate) {
            $groupedVouchers = $groupedVouchers->filter(function ($entry) use ($startDate, $endDate) {
                $voucherDate = \Carbon\Carbon::parse($entry['voucher_date']);
                return $voucherDate->between($startDate, $endDate);
            });
        }


        $totalInvoices = $voucherHeads->count();
        $firstVoucherDate = $groupedVouchers->min('voucher_date');
        $lastVoucherDate = $groupedVouchers->max('voucher_date');

        \Log::info('Combined Entries: ', $groupedVouchers->toArray());

        $dataTableResponse = datatables()->of($groupedVouchers)
            ->addColumn('credit', function ($entry) {
                return $entry['entry_type'] === 'credit' ? indian_format(abs($entry['amount'])) : '0.00';
            })
            ->addColumn('debit', function ($entry) {
                return $entry['entry_type'] === 'debit' ? indian_format(abs($entry['amount'])) : '0.00';
            })
            ->addColumn('running_balance', function ($entry) {
                return $entry['running_balance'] ?? "";
            })
            ->addColumn('voucher_type_name', function ($entry) {
                return $entry['voucher_type_name'];
            })
            ->addColumn('voucher_date', function ($entry) {
                return \Carbon\Carbon::parse($entry['voucher_date'])->format('d-M-Y');
            })
            ->addColumn('ledger_name', function ($entry) {
                return $entry['ledger_name'];
            })
            ->with([
                'first_voucher_date' => $firstVoucherDate,
                'last_voucher_date' => $lastVoucherDate,
                'total_invoices' => $totalInvoices
            ])
            ->toJson();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        \Log::info('Total execution time for CustomerController.getVoucherEntries:', ['time_taken' => $executionTime . ' seconds']);
        
        return $dataTableResponse;
    }

}
