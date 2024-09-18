<?php

namespace App\Http\Controllers\SuperAdmin;

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
        return view('superadmin.customers.index');
    }
    
    public function getData(Request $request)
    {

        $companyGuids = $this->reportService->companyData(); 

        if ($request->ajax()) {

            $query = TallyLedger::where('parent', 'Sundry Debtors')
                                ->whereIn('company_guid', $companyGuids);
            
            if ($request->has('filter_outstanding') && $request->filter_outstanding == 'true') {
                $query->where(function($query) {
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->pluck('ledger_guid');
                    
                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');
            
                    foreach ($totalSalesByGuid as $guid => $amount) {
                        if ($amount != 0) {
                            $query->orWhere('guid', $guid);
                        }
                    }
                });
            }

            if ($request->has('filter_ageing') && $request->filter_ageing == 'true') {
                $query->where(function($query) {
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->pluck('ledger_guid');
                    
                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');
            
                    foreach ($totalSalesByGuid as $guid => $amount) {
                        if ($amount != 0) {
                            $query->orWhere('guid', $guid);
                        }
                    }
                });
            }
            
            if ($request->has('filter_collection') && $request->filter_collection == 'true') {
                $query->where(function($query) {
                    $ledgerData = TallyVoucher::where('voucher_type', 'Receipt')
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
                            $query->orWhere('guid', $guid);
                        }
                    }
                });
            }

            if ($request->has('filter_sale') && $request->filter_sale == 'true') {
                $query->where(function($query) {
                    $ledgerSaleGuids = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
                        ->pluck('ledger_guid')
                        ->unique(); 
                    
                    $tallySaleVoucherIds = TallyVoucher::where('voucher_type', 'Sales')
                        ->whereIn('ledger_guid', $ledgerSaleGuids)
                        ->whereBetween('voucher_date', [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()])
                        ->pluck('id');
            
                    $totalSalesByGuid = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
                        ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
                        ->groupBy('ledger_guid')
                        ->selectRaw('ledger_guid, SUM(amount) as total_amount')
                        ->pluck('total_amount', 'ledger_guid');
                    
                    $guidToExclude = $totalSalesByGuid->filter(function ($amount) {
                        return $amount != 0;
                    })->keys();
            
                    $query->whereNotIn('guid', $guidToExclude);
                });
            }
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('sales_last_30_days', function ($data) {
                    $ledgerSaleData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Sales')
                        ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                        ->where('voucher_date', '<=', Carbon::now()->endOfDay())
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
                        ->pluck('ledger_guid');
        
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');
        
                    return number_format($totalSales, 2);
                })
                ->addColumn('overdue', function ($data) {
                    $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Sales')
                        ->pluck('ledger_guid');
        
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');
        
                    return number_format($totalSales, 2);
                })
                // ->addColumn('payment_collection', function ($data) {
                //     $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                //         ->where('voucher_type', 'Receipt')
                //         ->pluck('id', 'ledger_guid');

                //         // dd($ledgerData);
                //         Log::info('ledgerData: ', $ledgerData->toArray());
        
                //     $ledgerGuids = $ledgerData->keys();
                //     $tallyVoucherIds = $ledgerData->values();
        
                //     $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                //         ->whereIn('tally_voucher_id', $tallyVoucherIds)
                //         ->sum('amount');

                        
                //         Log::info('totalSales: ', ['total' => $totalSales]);
        
                //     return number_format($totalSales, 2);
                // })
                ->addColumn('payment_collection', function ($data) {
                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Receipt')
                        ->get(['id', 'ledger_guid']); 
                
                    $ledgerGuids = $ledgerData->pluck('ledger_guid')->toArray();
                    $tallyVoucherIds = $ledgerData->pluck('id')->toArray(); 
                
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                        ->whereIn('tally_voucher_id', $tallyVoucherIds)
                        ->sum('amount');
                
                    return number_format($totalSales, 2);
                })
                
                ->addColumn('payment_date', function ($data) {
                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Receipt')
                        ->pluck('id', 'ledger_guid');
        
                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();
        
                    $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
                        ->whereIn('id', $tallyVoucherIds)
                        ->orderBy('voucher_date', 'desc')
                        ->first();
        
                    if ($latestReceipt) {
                        return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('j F Y');
                    } else {
                        return '-';
                    }
                })
                ->make(true);
        }
    }
    

    



    // public function getData(Request $request)
    // {
    //     if ($request->ajax()) {
    //         $societies = TallyLedger::where('parent', 'Sundry Debtors');

    //         return DataTables::of($societies)
    //             ->addIndexColumn()
    //             ->addColumn('sales_last_30_days', function ($data) {

    //                 $ledgerSaleData = TallyVoucher::where('ledger_guid', $data->guid)
    //                 ->where('voucher_type', 'Sales')
    //                 ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
    //                     ->where('voucher_date', '<=', Carbon::now()->endOfDay())
    //                 ->pluck('id', 'ledger_guid');
    
    //                 $ledgerSaleGuids = $ledgerSaleData->keys();
    //                 $tallySaleVoucherIds = $ledgerSaleData->values();
    
    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerSaleGuids)
    //                 ->whereIn('tally_voucher_id', $tallySaleVoucherIds)
    //                 ->sum('amount');
    
    //                 return number_format(abs($totalSales), 2);
    //             })
    //             ->addColumn('outstanding', function ($data) {
    //                 $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Sales')
    //                     ->pluck('ledger_guid');

    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
    //                     ->sum('amount');

    //                 return number_format($totalSales, 2);
    //             })

    //             ->addColumn('overdue', function ($data) {
    //                 $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Sales')
    //                     ->pluck('ledger_guid');
    
    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
    //                     ->sum('amount');
    
    //                 return number_format($totalSales, 2);
    //             })
    //             ->addColumn('payment_collection', function ($data) {
    
    //                 $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
    //                 ->where('voucher_type', 'Receipt')
    //                 ->pluck('id', 'ledger_guid');
    
    //                 $ledgerGuids = $ledgerData->keys();
    //                 $tallyVoucherIds = $ledgerData->values();
    
    //                 $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
    //                 ->whereIn('tally_voucher_id', $tallyVoucherIds)
    //                 ->sum('amount');
    
    //                 return number_format($totalSales, 2);
    //             })
    //             ->addColumn('payment_date', function ($data) {
    
    //                 $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
    //                     ->where('voucher_type', 'Receipt')
    //                     ->pluck('id', 'ledger_guid');
                
    //                 $ledgerGuids = $ledgerData->keys();
    //                 $tallyVoucherIds = $ledgerData->values();
    
    //                 $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
    //                     ->whereIn('id', $tallyVoucherIds)
    //                     ->orderBy('voucher_date', 'desc') 
    //                     ->first();
                
    //                 if ($latestReceipt) {
    //                     return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('j F Y');
    //                 } else {
    //                     return '-'; 
    //                 }
    //             })
    //             ->make(true);
    //     }
    // }
    

    // public function otherLedgers(OtherCustomerDataTable $dataTable)
    // {
    //     return $dataTable->render('superadmin.customers.customer');
    // }

    public function otherLedgers()
    {
        return View('superadmin.customers.ledger');
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
                    ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                        ->where('voucher_date', '<=', Carbon::now()->endOfDay())
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
                        ->pluck('ledger_guid');

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');

                    return number_format($totalSales, 2);
                })

                ->addColumn('overdue', function ($data) {
                    $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Sales')
                        ->pluck('ledger_guid');
    
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');
    
                    return number_format($totalSales, 2);
                })
                ->addColumn('payment_collection', function ($data) {
    
                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Receipt')
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
                        ->pluck('id', 'ledger_guid');
                
                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();
    
                    $latestReceipt = TallyVoucher::whereIn('ledger_guid', $ledgerGuids)
                        ->whereIn('id', $tallyVoucherIds)
                        ->orderBy('voucher_date', 'desc') 
                        ->first();
                
                    if ($latestReceipt) {
                        return \Carbon\Carbon::parse($latestReceipt->voucher_date)->format('j F Y');
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
        return view('superadmin.customers._customers-view', compact('ledger'));
    }

    // public function getVoucherEntries($customer)
    // {
    //     $ledger = TallyLedger::where('guid', $customer)->firstOrFail();

    //         $voucherHeads = TallyVoucherHead::where('ledger_guid', $ledger->guid)
    //             ->with('voucherHead') 
    //             ->get();

    //         $voucherEntries = TallyVoucherAccAllocationHead::where('ledger_guid', $ledger->guid)
    //             ->with('voucherHead') 
    //             ->get();
        
    //     return datatables()->of($voucherEntries)
    //         ->addColumn('credit', function ($entry) {
    //             return $entry->entry_type == 'credit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
    //         })
    //         ->addColumn('debit', function ($entry) {
    //             return $entry->entry_type == 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
    //         })
    //         ->addColumn('voucher_number', function ($entry) {
    //             return $entry->voucherHead ? $entry->voucherHead->voucher_number : '';
    //         })
    //         ->addColumn('voucher_type', function ($entry) {
    //             return $entry->voucherHead ? $entry->voucherHead->voucher_type : '';
    //         })
    //         ->addColumn('voucher_date', function ($entry) {
    //             return $entry->voucherHead ? $entry->voucherHead->voucher_date : '';
    //         })
    //         ->make(true);
    // }


    public function getVoucherEntries($customer)
    {
        $companyGuids = $this->reportService->companyData();
        $ledger = TallyLedger::where('guid', $customer)
                                ->whereIn('company_guid', $companyGuids)
                                ->firstOrFail();
    
        // Fetch data from TallyVoucherHead
        $voucherHeads = TallyVoucherHead::where('ledger_guid', $ledger->guid)
            ->with('voucherHead')
            ->get();
    
        // Fetch data from TallyVoucherAccAllocationHead
        $voucherEntries = TallyVoucherAccAllocationHead::where('ledger_guid', $ledger->guid)
            ->with('voucherHead')
            ->get();
    
        // Combine both collections into one
        $combinedEntries = $voucherHeads->merge($voucherEntries);
    
        // Return the combined data to DataTables
        return datatables()->of($combinedEntries)
            ->addColumn('credit', function ($entry) {
                return $entry->entry_type == 'credit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
            })
            ->addColumn('debit', function ($entry) {
                return $entry->entry_type == 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '0.00';
            })
            ->addColumn('voucher_number', function ($entry) {
                return $entry->voucherHead ? $entry->voucherHead->voucher_number : '';
            })
            ->addColumn('voucher_type', function ($entry) {
                return $entry->voucherHead ? $entry->voucherHead->voucher_type : '';
            })
            ->addColumn('voucher_date', function ($entry) {
                return $entry->voucherHead ? $entry->voucherHead->voucher_date : '';
            })
            ->make(true);
    }
    
    


}
