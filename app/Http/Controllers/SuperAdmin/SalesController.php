<?php

namespace App\Http\Controllers\SuperAdmin;

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
use App\DataTables\SuperAdmin\SalesDataTable;

class SalesController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function index(SalesDataTable $dataTable)
    {
        return $dataTable->render('superadmin.sales.index');
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();
    
        if ($request->ajax()) {
            // Specify the table name for the ambiguous column
            $query = TallyVoucher::whereIn('tally_vouchers.company_guid', $companyGuids) // Here we specify the table name
                ->select(
                    'tally_vouchers.*', 
                    'tally_voucher_heads.entry_type', 
                    'tally_voucher_heads.amount', 
                    'tally_ledgers.parent', 
                    'tally_ledgers.bill_credit_period', 
                    'tally_ledgers.gst_in', 
                    'tally_ledgers.phone_no', 
                    'tally_ledgers.email'
                )
                ->leftJoin('tally_voucher_heads', function ($join) {
                    $join->on('tally_vouchers.party_ledger_name', '=', 'tally_voucher_heads.ledger_name')
                        ->on('tally_vouchers.id', '=', 'tally_voucher_heads.tally_voucher_id');
                })
                ->leftJoin('tally_ledgers', 'tally_vouchers.party_ledger_name', '=', 'tally_ledgers.language_name')
                ->leftJoin('tally_voucher_heads as related_heads', 'tally_voucher_heads.tally_voucher_id', '=', 'related_heads.tally_voucher_id')
                ->groupBy(
                    'tally_vouchers.id', 
                    'tally_vouchers.party_ledger_name', 
                    'tally_vouchers.voucher_date', 
                    'tally_vouchers.voucher_number', 
                    'tally_vouchers.voucher_type', 
                    'tally_ledgers.parent', 
                    'tally_ledgers.bill_credit_period', 
                    'tally_ledgers.gst_in', 
                    'tally_ledgers.phone_no', 
                    'tally_ledgers.email', 
                    'tally_voucher_heads.entry_type', 
                    'tally_voucher_heads.amount'
                )
                ->selectRaw('GROUP_CONCAT(DISTINCT related_heads.ledger_name) as related_ledger_names')
                ->selectRaw('SUM(CASE WHEN related_heads.ledger_name LIKE "%IGST @18%" THEN related_heads.amount ELSE 0 END) as igst_amount')
                ->selectRaw('SUM(CASE WHEN related_heads.ledger_name LIKE "%Round Off%" THEN related_heads.amount ELSE 0 END) as round_off_amount')
                ->where('tally_vouchers.voucher_type', 'Sales');
    
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
    
                if ($startDate && $endDate) {
                    try {
                        $startDate = Carbon::parse($startDate)->startOfDay();
                        $endDate = Carbon::parse($endDate)->endOfDay();
                        $query->whereBetween('voucher_date', [$startDate, $endDate]);
                    } catch (\Exception $e) {
                        \Log::error('Date parsing error: ' . $e->getMessage());
                    }
                }
            }
    
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('debit', function ($entry) {
                    return $entry->entry_type === 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
                })
                ->make(true);
        }
    }
    
    public function AllSaleItemReports($saleItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleItemId);
       
        $saleItemName = TallyVoucher::where('party_ledger_name', $saleItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        $saleReceiptItem = $saleItemName->firstWhere('voucher_type', 'Receipt');
    
         if ($saleReceiptItem) {
            $voucherHeadsSaleReceipt = TallyVoucherHead::where('tally_voucher_id', $saleReceiptItem->id)
                ->where('entry_type', 'credit')
                ->get();
        } else {
            $voucherHeadsSaleReceipt = collect(); 
        }


        $ledgerData = TallyLedger::where('language_name', $saleItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
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

        $menuItems = TallyVoucher::where('voucher_type', 'Sales')->whereIn('company_guid', $companyGuids)->get();

        return view('superadmin.sales._sale_item_list', [
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
