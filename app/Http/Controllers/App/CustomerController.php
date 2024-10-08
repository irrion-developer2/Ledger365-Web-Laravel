<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use App\Models\TallyVoucherAccAllocationHead;
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

            // $customers = TallyLedger::where('parent', 'Sundry Debtors')
            //                     ->whereIn('company_guid', $companyGuids);

            $customers = TallyLedger::select('tally_ledgers.company_guid','tally_ledgers.guid','tally_ledgers.language_name','tally_ledgers.party_gst_in') 
                    ->where('parent', 'Sundry Debtors')
                    ->whereIn('tally_ledgers.company_guid', $companyGuids) 
                    ->leftJoin('tally_vouchers', function($join) {
                        $join->on('tally_ledgers.guid', '=', 'tally_vouchers.ledger_guid')
                            ->where('tally_vouchers.is_cancelled', 'No')
                            ->where('tally_vouchers.is_optional', 'No');
                    })
                    ->leftJoin('tally_voucher_heads', 'tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id')
                    
                    ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" THEN tally_voucher_heads.amount END), 0) as total_sales')
                    ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Sales" AND tally_vouchers.is_cancelled = "No" AND tally_vouchers.is_optional = "No" THEN tally_voucher_heads.amount END), 0) as outstanding')
                    ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Receipt" THEN tally_voucher_heads.amount END), 0) as payment_collection')
    
                  
                    ->groupBy('tally_ledgers.guid')
                    ->get();

            // dd($customers);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;

            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $customDateRange = $request->get('custom_date_range');

            // Handle custom date ranges
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
                }
            }

            if ($startDate && $endDate) {
                $customers->whereHas('vouchers', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('voucher_date', [$startDate, $endDate]);
                });
            }

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            if ($request->has('filter_outstanding') && $request->filter_outstanding == 'true') {
                $customers->where(function($customers) {
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->pluck('ledger_guid');

                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');

                    foreach ($totalSalesByGuid as $guid => $amount) {
                        if ($amount != 0) {
                            $customers->orWhere('guid', $guid);
                        }
                    }
                });
            }

            if ($request->has('filter_ageing') && $request->filter_ageing == 'true') {
                $customers->where(function($customers) {
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->pluck('ledger_guid');

                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');

                    foreach ($totalSalesByGuid as $guid => $amount) {
                        if ($amount != 0) {
                            $customers->orWhere('guid', $guid);
                        }
                    }
                });
            }

            if ($request->has('filter_collection') && $request->filter_collection == 'true') {
                $customers->where(function($customers) {
                    $ledgerData = TallyVoucher::where('voucher_type', 'Receipt')
                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                    ->pluck('id', 'ledger_guid');

                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();

                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->whereIn('tally_voucher_id', $tallyVoucherIds)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');

                    foreach ($totalSalesByGuid as $guid => $amount) {
                        if ($amount != 0) {
                            $customers->orWhere('guid', $guid);
                        }
                    }
                });
            }

            if ($request->has('filter_sale') && $request->filter_sale == 'true') {
                $customers->where(function($customers) {
                    $ledgerSaleGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        // ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
                        ->pluck('ledger_guid')
                        ->unique();

                    $tallySaleVoucherIds = TallyVoucher::where('voucher_type', 'Sales')
                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->whereIn('ledger_guid', $ledgerSaleGuids)
                        // ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
                        ->pluck('id');

                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
                        ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');

                    $guidToExclude = $totalSalesByGuid->filter(function ($amount) {
                        return $amount != 0;
                    })->keys();

                    $customers->whereNotIn('guid', $guidToExclude);
                });
            }

            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('sales_last_30_days', function ($data) {

                    $totalSales = $data->total_sales;

                    return number_format(abs($totalSales), 2);
                })
                ->addColumn('outstanding', function ($data) {

                    $outstanding = $data->outstanding;
                    // dd($outstanding);

                    return number_format($outstanding, 2);
                })
                ->addColumn('overdue', function ($data) {
                    $outstanding = $data->outstanding;
                    // dd($outstanding);

                    return number_format($outstanding, 2);
                })
                ->addColumn('payment_collection', function ($data) {
                    $payment_collection = $data->payment_collection;
                    // dd($outstanding);

                    return number_format($payment_collection, 2);
                })
                ->make(true);

                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

                return $dataTable;
        }
    }


    // public function getData(Request $request)
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $customers = TallyLedger::where('parent', 'Sundry Debtors')
    //                             ->whereIn('company_guid', $companyGuids);

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;

    //         Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

    //         $startDate = $request->get('start_date');
    //         $endDate = $request->get('end_date');

    //         $customDateRange = $request->get('custom_date_range');

    //         // Handle custom date ranges
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
    //             }
    //         }

    //         if ($startDate && $endDate) {
    //             $customers->whereHas('vouchers', function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('voucher_date', [$startDate, $endDate]);
    //             });
    //         }

    //         Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
    //         Log::info('Start date:', ['startDate' => $startDate]);
    //         Log::info('End date:', ['endDate' => $endDate]);

    //         if ($request->has('filter_outstanding') && $request->filter_outstanding == 'true') {
    //             $customers->where(function($customers) {
    //                 $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->pluck('ledger_guid');

    //                 $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
    //                     ->groupBy('ledger_guid')
    //                     ->selectRaw('ledger_guid, SUM(amount) as total_amount')
    //                     ->pluck('total_amount', 'ledger_guid');

    //                 foreach ($totalSalesByGuid as $guid => $amount) {
    //                     if ($amount != 0) {
    //                         $customers->orWhere('guid', $guid);
    //                     }
    //                 }
    //             });
    //         }

    //         if ($request->has('filter_ageing') && $request->filter_ageing == 'true') {
    //             $customers->where(function($customers) {
    //                 $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->pluck('ledger_guid');

    //                 $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
    //                     ->groupBy('ledger_guid')
    //                     ->selectRaw('ledger_guid, SUM(amount) as total_amount')
    //                     ->pluck('total_amount', 'ledger_guid');

    //                 foreach ($totalSalesByGuid as $guid => $amount) {
    //                     if ($amount != 0) {
    //                         $customers->orWhere('guid', $guid);
    //                     }
    //                 }
    //             });
    //         }

    //         if ($request->has('filter_collection') && $request->filter_collection == 'true') {
    //             $customers->where(function($customers) {
    //                 $ledgerData = TallyVoucher::where('voucher_type', 'Receipt')
    //                 ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                 ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                 ->pluck('id', 'ledger_guid');

    //                 $ledgerGuids = $ledgerData->keys();
    //                 $tallyVoucherIds = $ledgerData->values();

    //                 $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
    //                     ->whereIn('tally_voucher_id', $tallyVoucherIds)
    //                     ->groupBy('ledger_guid')
    //                     ->selectRaw('ledger_guid, SUM(amount) as total_amount')
    //                     ->pluck('total_amount', 'ledger_guid');

    //                 foreach ($totalSalesByGuid as $guid => $amount) {
    //                     if ($amount != 0) {
    //                         $customers->orWhere('guid', $guid);
    //                     }
    //                 }
    //             });
    //         }

    //         if ($request->has('filter_sale') && $request->filter_sale == 'true') {
    //             $customers->where(function($customers) {
    //                 $ledgerSaleGuids = TallyVoucher::where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     // ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
    //                     ->pluck('ledger_guid')
    //                     ->unique();

    //                 $tallySaleVoucherIds = TallyVoucher::where('voucher_type', 'Sales')
    //                 ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                 ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->whereIn('ledger_guid', $ledgerSaleGuids)
    //                     // ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
    //                     ->pluck('id');

    //                 $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
    //                     ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
    //                     ->groupBy('ledger_guid')
    //                     ->selectRaw('ledger_guid, SUM(amount) as total_amount')
    //                     ->pluck('total_amount', 'ledger_guid');

    //                 $guidToExclude = $totalSalesByGuid->filter(function ($amount) {
    //                     return $amount != 0;
    //                 })->keys();

    //                 $customers->whereNotIn('guid', $guidToExclude);
    //             });
    //         }

    //         $dataTable = DataTables::of($customers)
    //             ->addIndexColumn()
    //             ->addColumn('sales_last_30_days', function ($data) {
    //                 $ledgerSaleData = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     // ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
    //                     // ->where('voucher_date', '<=', Carbon::now()->endOfDay())
    //                     ->pluck('id', 'ledger_guid');

    //                 $ledgerSaleGuids = $ledgerSaleData->keys();
    //                 $tallySaleVoucherIds = $ledgerSaleData->values();

    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
    //                     ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
    //                     ->sum('amount');

    //                 return number_format(abs($totalSales), 2);
    //             })
    //             ->addColumn('outstanding', function ($data) {
    //                 $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->pluck('ledger_guid');

    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
    //                     ->sum('amount');

    //                 return number_format($totalSales, 2);
    //             })
    //             ->addColumn('overdue', function ($data) {
    //                 $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Sales')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->pluck('ledger_guid');

    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
    //                     ->sum('amount');

    //                 return number_format($totalSales, 2);
    //             })
    //             ->addColumn('payment_collection', function ($data) {
    //                 $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Receipt')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->get(['id', 'ledger_guid']);

    //                 $ledgerGuids = $ledgerData->pluck('ledger_guid')->toArray();
    //                 $tallyVoucherIds = $ledgerData->pluck('id')->toArray();

    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
    //                     ->whereIn('tally_voucher_id', $tallyVoucherIds)
    //                     ->sum('amount');

    //                 return number_format($totalSales, 2);
    //             })
    //             ->addColumn('payment_date', function ($data) {
    //                 $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Receipt')
    //                     ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                     ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                     ->pluck('id', 'ledger_guid');

    //                 $ledgerGuids = $ledgerData->keys();
    //                 $tallyVoucherIds = $ledgerData->values();

    //                 $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
    //                     ->whereIn('id', $tallyVoucherIds)
    //                     ->orderBy('voucher_date', 'desc')
    //                     ->first();

    //                 if ($latestReceipt) {
    //                     return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('d-M-Y');
    //                 } else {
    //                     return '-';
    //                 }
    //             })
    //             ->make(true);

    //             $endTime = microtime(true);
    //             $executionTime = $endTime - $startTime;
    //             Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

    //             return $dataTable;
    //     }
    // }

    public function otherLedgers()
    {
        return View('app.customers.ledger');
    }

    public function ledgergetData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $societies = TallyLedger::whereNotIn('parent', ['Sundry Debtors', 'Sundry Creditors'])
                                        ->whereIn('company_guid', $companyGuids);

            return DataTables::of($societies)
                ->addIndexColumn()
                ->addColumn('sales_last_30_days', function ($data) {

                    $ledgerSaleData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Sales')
                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                    // ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                    //     ->where('voucher_date', '<=', Carbon::now()->endOfDay())
                    ->pluck('id', 'ledger_guid');

                    $ledgerSaleGuids = $ledgerSaleData->keys();
                    $tallySaleVoucherIds = $ledgerSaleData->values();

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
                    ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
                    ->sum('amount');

                    return number_format(abs($totalSales), 2);
                })
                ->addColumn('outstanding', function ($data) {
                    $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->pluck('ledger_guid');

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');

                    return number_format($totalSales, 2);
                })
                ->addColumn('overdue', function ($data) {
                    $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->pluck('ledger_guid');

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');

                    return number_format($totalSales, 2);
                })
                ->addColumn('payment_collection', function ($data) {

                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Receipt')
                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                    ->pluck('id', 'ledger_guid');

                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                    ->whereIn('tally_voucher_id', $tallyVoucherIds)
                    ->sum('amount');

                    return number_format($totalSales, 2);
                })
                ->addColumn('payment_date', function ($data) {

                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Receipt')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->pluck('id', 'ledger_guid');

                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();

                    $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
                        ->whereIn('id', $tallyVoucherIds)
                        ->orderBy('voucher_date', 'desc')
                        ->first();

                    if ($latestReceipt) {
                        return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('d-M-Y');
                    } else {
                        return '-';
                    }
                })
                ->make(true);
        }
    }

    public function show($customer)
    {

        $companyGuids = $this->reportService->companyData();

        $ledger = TallyLedger::where('guid', $customer)
                                ->whereIn('company_guid', $companyGuids)
                                ->firstOrFail();

        // dd($ledger);
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
        
        \Log::info('Query 1: ', \DB::getQueryLog());
    
        $voucherHeads = TallyVoucherHead::where('ledger_guid', $ledger->guid)
            ->whereHas('voucherHead', function ($query) {
                $query->where('is_cancelled', '!=', 'Yes')
                    ->where('is_optional', '!=', 'Yes');
            })
            ->with('voucherHead')
            ->get();
    
        \Log::info('Query 2: ', \DB::getQueryLog());
    
        $voucherEntries = TallyVoucherAccAllocationHead::where('ledger_guid', $ledger->guid)
            ->whereHas('voucherHead', function ($query) {
                $query->where('is_cancelled', '!=', 'Yes')
                    ->where('is_optional', '!=', 'Yes');
            })
            ->with('voucherHead')
            ->get();
    
        \Log::info('Query 3: ', \DB::getQueryLog());
    
        $combinedEntries = $voucherHeads->merge($voucherEntries);
    
        $runningBalance = 0;
        $openingBalanceAdded = false; // Flag to ensure opening balance is added only once

        $combinedEntries = $combinedEntries->map(function ($entry) use (&$runningBalance, &$openingBalanceAdded, $ledger) {
            $Amount = floatval($entry->amount ?? 0);
            $openingBalance = floatval($ledger->opening_balance ?? 0);

            // Add the opening balance only once (to the first entry)
            if (!$openingBalanceAdded) {
                $runningBalance += $openingBalance;
                $openingBalanceAdded = true; // Mark the flag as true after adding the opening balance
            }

            $runningBalance += $Amount;
            $entry->running_balance = ($runningBalance == 0 || empty($runningBalance)) ? '0.000' : number_format($runningBalance, 3, '.', '');

            return $entry;
        });

    
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        if ($startDate && $endDate) {
            $combinedEntries = $combinedEntries->filter(function ($entry) use ($startDate, $endDate) {
                $voucherDate = \Carbon\Carbon::parse($entry->voucherHead->voucher_date);
                return $voucherDate->between($startDate, $endDate);
            });
        }
    
        \Log::info('Combined Entries: ', $combinedEntries->toArray());
    
        $dataTableResponse = datatables()->of($combinedEntries)
            ->addColumn('credit', function ($entry) {
                return $entry->entry_type == 'credit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
            })
            ->addColumn('debit', function ($entry) {
                return $entry->entry_type == 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
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
            ->toJson();
    
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
    
        \Log::info('Total execution time for CustomerController.getVoucherEntries:', ['time_taken' => $executionTime . ' seconds']);
        
        return $dataTableResponse;
    }
    
}
