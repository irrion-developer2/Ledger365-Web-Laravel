<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucher;
use App\Models\TallyLedger;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.sales.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);
      
            $salesQuery = DB::table('tally_vouchers as tv')
            ->select(
                'tl.ledger_name',
                'tv.voucher_date',
                'tv.voucher_type_id',
                'tv.voucher_number',
                DB::raw('ABS(tvh.amount) as invoice_amount'),
                'tv.place_of_supply'
            )
            ->leftJoin('tally_voucher_heads as tvh', 'tv.voucher_id', '=', 'tvh.voucher_id')
            ->leftJoin('tally_ledgers as tl', 'tvh.ledger_id', '=', 'tl.ledger_id')
            ->leftJoin('tally_voucher_types as tvt', 'tv.voucher_type_id', '=', 'tvt.voucher_type_id')
            ->where('tvt.voucher_type_name', 'Sales')
            ->whereIn('tv.company_id', $companyIds)
            ->where('tvh.is_party_ledger', 1);

            Log::info("Sales Query");        
            Log::info($this->reportService->getFinalQuery($salesQuery));

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
                $salesQuery->whereBetween('tv.voucher_date', [$startDate, $endDate]);
            }
    
            $sales = $salesQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for SalesController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($sales)
                ->addIndexColumn()
                ->addColumn('debit', function ($data) {
                    $totalDebit = $data->invoice_amount;
                    return indian_format(abs($totalDebit));
                })
                ->addColumn('voucher_date', function ($entry) {
                    return \Carbon\Carbon::parse($entry->voucher_date)->format('d-M-Y');
                })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for SalesController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }
    
    public function AllSaleItemReports($saleItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleItemId);

        $saleItemName = TallyVoucher::where('party_ledger_name', $saleItem->party_ledger_name)
                                    ->whereNot('is_cancelled', 'Yes')
                                    ->whereNot('is_optional', 'Yes')
                                    ->whereIn('company_guid', $companyGuids)->get();
        $saleReceiptItem = $saleItemName->firstWhere('voucher_type', 'Receipt');

         if ($saleReceiptItem) {
            $voucherHeadsSaleReceipt = TallyVoucherHead::where('tally_voucher_id', $saleReceiptItem->id)
                ->where('entry_type', 'credit')
                ->get();
        } else {
            $voucherHeadsSaleReceipt = collect();
        }


        $ledgerData = TallyLedger::where('name', $saleItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        if ($ledgerData instanceof \Illuminate\Support\Collection) {
            $ledgerItem = $ledgerData->first();
        } else {
            $ledgerItem = $ledgerData;
        }

        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($saleItem->voucher_date);
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);


        $voucherHeadsName = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->get();
            $successfulAllocations = [];
            foreach ($voucherHeadsName as $voucherHead) {
                $id = $voucherHead->id;

                $bankAllocations = TallyBankAllocation::where('head_id', $id)->get();
                if ($bankAllocations->isNotEmpty()) {
                    $successfulAllocations[] = [
                        'voucher_head' => $voucherHead,
                        'bank_allocations' => $bankAllocations,
                    ];
                }
            }
        $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $saleItem->party_ledger_name)->get();

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->get();

        $gstVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($saleItem) {
            return $voucherHead->ledger_name !== $saleItem->party_ledger_name;
        });


        $voucherItems = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->get();
        $uniqueGstLedgerSources = $voucherItems->pluck('gst_ledger_source')->unique();
        $totalCountItems = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $totalCountHeads = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->count();
        $subtotalsamount = $voucherItems->sum('amount');

        $menuItems = TallyVoucher::where('voucher_type', 'Sales')
                                    ->whereNot('is_cancelled', 'Yes')
                                    ->whereNot('is_optional', 'Yes')
                                    ->whereIn('company_guid', $companyGuids)
                                    ->get();

        return view('app.sales._sale_item_list', [
            'saleItem' => $saleItem,
            'ledgerData' => $ledgerData,
            'voucherHeads' => $voucherHeads,
            'gstVoucherHeads' => $gstVoucherHeads,
            'totalCountItems' => $totalCountItems,
            'uniqueGstLedgerSources' => $uniqueGstLedgerSources,
            'subtotalsamount' => $subtotalsamount,
            'saleReceiptItem' => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate' => $dueDate,
            'saleItemId' => $saleItemId ,
            'menuItems' => $menuItems,
            'pendingVoucherHeads' => $pendingVoucherHeads,
            'totalCountLinkHeads' => $totalCountLinkHeads,
            'totalCountHeads' => $totalCountHeads
        ]);
    }

    public function getSaleItemData($saleItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleItemId);
        $saleItemName = $saleItem->party_ledger_name;

        $query = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->get();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }

}
