<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyCompany;
use App\Models\TallyVoucher;
use App\Models\TallyLedger;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherAccAllocationHead;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\ReportService;

class ColumnarController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.columnar.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $columnarsQuery = TallyVoucher::join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                ->join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                ->where('tally_voucher_types.voucher_type_name', 'Sales')
                ->where('tally_vouchers.is_cancelled', 0)
                ->where('tally_vouchers.is_optional', 0)
                ->whereIn('tally_vouchers.company_id', $companyIds)
                ->select(
                    'tally_vouchers.voucher_date',
                    'tally_vouchers.voucher_number',
                    'tally_vouchers.voucher_id',
                    'tally_vouchers.buyer_name',
                    'tally_vouchers.buyer_addr',
                    'tally_vouchers.gst_registration_type',
                    'tally_vouchers.buyer_gstin',
                    'tally_vouchers.place_of_supply',
                    'tally_voucher_types.voucher_type_name',
                    'tally_ledgers.ledger_name',
                    'tally_ledgers.ledger_id',
                    'tally_ledgers.state',
                    'tally_ledgers.country',
                );

            // dd($columnars);

            Log::info("Columnar Query");        
            Log::info($this->reportService->getFinalQuery($columnarsQuery));

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
                $columnarsQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }
    
            $columnars = $columnarsQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ColumnarController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    

            $dataTable = DataTables::of($columnars)
                ->addIndexColumn()
                ->addColumn('gross_total', function ($data) use ($companyIds){

                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                                ->where('entry_type', 'debit')
                                ->where('ledger_id', $data->ledger_id)
                                ->get();

                    $totalAmount = $amtHead->sum('amount');

                    return indian_format(abs($totalAmount), 3);
                })
                ->addColumn('taxable_value', function ($data) use ($companyIds){
                    $excludedLedgerNames = is_array($data->ledger_name)
                        ? $data->ledger_name
                        : explode(',', $data->ledger_name);
                    $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));

                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Sales Accounts']);
                    })
                    ->get();
                    $totalAmount = $amtHead->sum('amount');

                    return indian_format($totalAmount, 3);
                })
                ->addColumn('igst', function ($data) use ($companyIds){
                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['IGST']);
                    })
                    ->get();
                    $totalAmount = $amtHead->sum('amount');

                    return indian_format(abs($totalAmount), 3);
                })
                ->addColumn('sgst', function ($data) use ($companyIds){
                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['SGST/UTGST']);
                    })
                    ->get();
                    $totalAmount = $amtHead->sum('amount');

                    return indian_format(abs($totalAmount), 3);
                })
                ->addColumn('cgst', function ($data) use ($companyIds){
                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['CGST']);
                    })
                    ->get();
                    $totalAmount = $amtHead->sum('amount');

                    return indian_format(abs($totalAmount), 3);
                })
                ->addColumn('round_off', function ($data) use ($companyIds){
                    $excludedLedgerNames = is_array($data->ledger_name)
                        ? $data->ledger_name
                        : explode(',', $data->ledger_name);
                    $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));

                    $amtHead = TallyVoucherHead::where('voucher_id', $data->voucher_id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Indirect Expenses']);
                        $query->whereNotIn('ledger_name', $excludedLedgerNames);
                    })
                    ->get();

                    $totalAmount = $amtHead->sum('amount');

                    return indian_format($totalAmount, 3);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ColumnarController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
