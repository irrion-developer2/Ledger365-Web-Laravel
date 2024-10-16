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
use App\DataTables\SuperAdmin\CustomerDataTable;
use App\DataTables\SuperAdmin\OtherCustomerDataTable;
use Stancl\Tenancy\Facades\Tenancy;
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
        $companyGuids = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $customersQuery = TallyLedger::select('tally_ledgers.company_guid', 'tally_ledgers.guid', 'tally_ledgers.language_name', 'tally_ledgers.party_gst_in')
                ->where('parent', 'Sundry Debtors')
                ->whereIn('tally_ledgers.company_guid', $companyGuids)
                ->leftJoin('tally_vouchers', function ($join) {
                    $join->on('tally_ledgers.guid', '=', 'tally_vouchers.ledger_guid')
                        ->where('tally_vouchers.is_cancelled', 'No')
                        ->where('tally_vouchers.is_optional', 'No');
                })
                ->leftJoin('tally_voucher_heads', 'tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as total_sales')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as outstanding')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Receipt" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as payment_collection')
                ->groupBy('tally_ledgers.guid');

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
        $companyGuids = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $customersQuery = TallyLedger::select('tally_ledgers.company_guid', 'tally_ledgers.guid', 'tally_ledgers.language_name', 'tally_ledgers.party_gst_in')
                ->whereNotIn('parent', ['Sundry Debtors', 'Sundry Creditors'])
                ->whereIn('tally_ledgers.company_guid', $companyGuids)
                ->leftJoin('tally_vouchers', function ($join) {
                    $join->on('tally_ledgers.guid', '=', 'tally_vouchers.ledger_guid')
                        ->where('tally_vouchers.is_cancelled', 'No')
                        ->where('tally_vouchers.is_optional', 'No');
                })
                ->leftJoin('tally_voucher_heads', 'tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as total_sales')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as outstanding')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Receipt" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as payment_collection')
                ->groupBy('tally_ledgers.guid');

            Log::info("Other Ledger Query");        
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
            Log::info('Total first db request execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime1 . ' seconds']);
    
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
            Log::info('Total end execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

    public function show($customer)
    {
        $companyGuids = $this->reportService->companyData();

        $ledger = TallyLedger::where('guid', $customer)
                                ->whereIn('company_guid', $companyGuids)
                                ->firstOrFail();

        return view('app.customers._customers-view', compact('ledger'));
    }

    public function getVoucherEntries($customer, Request $request)
    {
        $startTime = microtime(true);
        
        \DB::enableQueryLog();
        $companyGuids = $this->reportService->companyData();
        
        $ledger = TallyLedger::where('guid', $customer)
            ->whereIn('company_guid', $companyGuids)
            ->firstOrFail();
        
    
        $voucherHeads = TallyVoucherHead::where('ledger_guid', $ledger->guid)
            ->whereHas('voucherHead', function ($query) {
                $query->where('is_cancelled', '!=', 'Yes')
                    ->where('is_optional', '!=', 'Yes')
                    ->orderBy('voucher_date', 'asc');
            })
            ->with('voucherHead')
            ->get();

        // dd($voucherHeads);
    
        \Log::info('Query 2: ', \DB::getQueryLog());
    
        \Log::info('Query 3: ', \DB::getQueryLog());
    
        $runningBalance = 0;
        $openingBalanceAdded = false;

        $voucherHeads = $voucherHeads->map(function ($entry) use (&$runningBalance, &$openingBalanceAdded, $ledger) {
            $Amount = floatval($entry->amount ?? 0);
            $openingBalance = floatval($ledger->opening_balance ?? 0);

            if (!$openingBalanceAdded) {
                $runningBalance += $openingBalance;
                $openingBalanceAdded = true; 
            }

            $runningBalance += $Amount;
            $entry->running_balance = ($runningBalance == 0 || empty($runningBalance)) ? '0.00' : indian_format($runningBalance);

            return $entry;
        });

    
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $voucherHeads = $voucherHeads->filter(function ($entry) use ($startDate, $endDate) {
                $voucherDate = \Carbon\Carbon::parse($entry->voucherHead->voucher_date);
                return $voucherDate->between($startDate, $endDate);
            });
        }

        $firstVoucherDate = $voucherHeads->min('voucherHead.voucher_date');
        $lastVoucherDate = $voucherHeads->max('voucherHead.voucher_date');
    
        \Log::info('Combined Entries: ', $voucherHeads->toArray());
    
        $dataTableResponse = datatables()->of($voucherHeads)
            ->addColumn('credit', function ($entry) {
                return $entry->entry_type == 'credit' ? indian_format(abs($entry->amount), 2, '.', ',') : '0.00';
            })
            ->addColumn('debit', function ($entry) {
                return $entry->entry_type == 'debit' ? indian_format(abs($entry->amount), 2, '.', ',') : '0.00';
            })
            ->addColumn('running_balance', function ($entry) use ($ledger) {
                return $entry->running_balance ? $entry->running_balance : "";
            })
            ->addColumn('voucher_number', function ($entry) {
                return $entry->voucherHead ? $entry->voucherHead->voucher_number : '';
            })
            ->addColumn('opening_balance', function () use ($ledger) {
                return $ledger->opening_balance;
            })
            ->addColumn('voucher_type', function ($entry) {
                return $entry->voucherHead ? $entry->voucherHead->voucher_type : '';
            })
            ->addColumn('voucher_date', function ($entry) {
                if ($entry->voucherHead && $entry->voucherHead->voucher_date) {
                    return \Carbon\Carbon::parse($entry->voucherHead->voucher_date)->format('d-M-Y'); // Format: 02-Aug-2024
                }
                return '';
            })
            ->with([
                'first_voucher_date' => $firstVoucherDate,
                'last_voucher_date' => $lastVoucherDate
            ])
            ->toJson();
    
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
    
        \Log::info('Total execution time for CustomerController.getVoucherEntries:', ['time_taken' => $executionTime . ' seconds']);
        
        return $dataTableResponse;
    }
    
}
