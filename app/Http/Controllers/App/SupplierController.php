<?php

namespace App\Http\Controllers\App;

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
        return View('app.suppliers.index');
    }
    
    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $suppliersQuery = TallyLedger::select('tally_ledgers.company_guid', 'tally_ledgers.guid', 'tally_ledgers.language_name', 'tally_ledgers.party_gst_in')
                ->where('parent', 'Sundry Creditors')
                ->whereIn('tally_ledgers.company_guid', $companyGuids)
                ->leftJoin('tally_vouchers', function ($join) {
                    $join->on('tally_ledgers.guid', '=', 'tally_vouchers.ledger_guid')
                        ->where('tally_vouchers.is_cancelled', 'No')
                        ->where('tally_vouchers.is_optional', 'No');
                })
                ->leftJoin('tally_voucher_heads', 'tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Purchase" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as total_purchase')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Purchase" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as outstanding')
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_vouchers.voucher_type = "Payment" AND tally_ledgers.guid = tally_voucher_heads.ledger_guid THEN tally_voucher_heads.amount END), 0) as payment_collection')
                ->groupBy('tally_ledgers.guid');

            Log::info("Supplier Query");        
            Log::info($this->reportService->getFinalQuery($suppliersQuery));

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
                $suppliersQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }
    
            $suppliers = $suppliersQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for SupplierController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($suppliers)
                ->addIndexColumn()
                ->addColumn('purchase', function ($data) {
                    $totalPurchase = $data->total_purchase;
                    return indian_format(abs($totalPurchase));
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
            Log::info('Total end execution time for SupplierController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
