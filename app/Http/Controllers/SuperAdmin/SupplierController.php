<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use App\Services\ReportService;
use App\DataTables\SuperAdmin\SupplierDataTable;

class SupplierController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return View('superadmin.suppliers.index');
    }

    public function getData(Request $request)
    {

        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $query = TallyLedger::where('parent', 'Sundry Creditors')
                                ->whereIn('company_guid', $companyGuids);


            if ($request->has('filter_outstanding') && $request->filter_outstanding == 'true') {
                $query->where(function($query) {
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Purchase')
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
                    $ledgerGuids = TallyVoucher::where('voucher_type', 'Purchase')
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
            
            if ($request->has('filter_payment') && $request->filter_payment == 'true') {
                $query->where(function($query) {
                    $ledgerData = TallyVoucher::where('voucher_type', 'Payment')
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

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('purchase_last_30_days', function ($data) {

                    $ledgerSaleData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Purchase')
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
                        ->where('voucher_type', 'Purchase')
                        ->pluck('ledger_guid');

                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');

                    return number_format($totalSales, 2);
                })
                ->addColumn('overdue', function ($data) {
                    $ledgerIds = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Purchase')
                        ->pluck('ledger_guid');
    
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerIds)
                        ->sum('amount');
    
                    return number_format($totalSales, 2);
                }) 
                ->addColumn('return30', function ($data) {

                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                    ->where('voucher_type', 'Debit Note')
                    ->where('voucher_date', '>=', Carbon::now()->subDays(30)->startOfDay())
                    ->where('voucher_date', '<=', Carbon::now()->endOfDay())
                    ->pluck('id', 'ledger_guid');
    
                    $ledgerGuids = $ledgerData->keys();
                    $tallyVoucherIds = $ledgerData->values();
    
                    $totalSales = TallyVoucherHead::whereIn('ledger_guid', $ledgerGuids)
                    ->whereIn('tally_voucher_id', $tallyVoucherIds)
                    ->sum('amount');
    
                    return number_format(abs($totalSales), 2);
    
                })
                ->addColumn('overdue_date', function ($data) {

                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Purchase')
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
                ->addColumn('payment_collection', function ($data) {
                    $ledgerData = TallyVoucher::where('ledger_guid', $data->guid)
                        ->where('voucher_type', 'Payment')
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
                        ->where('voucher_type', 'Payment')
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

}
