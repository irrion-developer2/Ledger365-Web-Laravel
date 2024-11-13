<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyCompany;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        return view('app.reports.index');
    }


    public function AllVoucherHeadReports($voucherHeadId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherHead = TallyLedger::whereIn('company_id', $companyIds)->where('guid', $voucherHeadId)->firstOrFail();
        $voucherHeadName = $voucherHead->name;

        $menuItems = TallyLedger::where('name', $voucherHead->name)->whereIn('company_id', $companyIds)->get();

        return view('app.reports._voucher_heads', [
            'voucherHead' => $voucherHead,
            'voucherHeadId' => $voucherHeadId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getVoucherHeadData($cashBankLedgerId)
    {
        $companyIds = $this->reportService->companyData();

        $cashBankLedger = TallyLedger::whereIn('company_id', $companyIds)->where('guid', $cashBankLedgerId)->firstOrFail();
        $cashBankLedgerName = $cashBankLedger->name;
        $cashBankLadgerGuid = $cashBankLedger->guid;

        $query = TallyLedger::select('tally_ledgers.*',
                                    'tally_voucher_heads.entry_type',
                                    'tally_voucher_heads.amount',
                                    'tally_voucher_heads.ledger_name',
                                    'tally_vouchers.voucher_type' ,
                                    'tally_vouchers.voucher_date',
                                    'tally_vouchers.voucher_number',
                                    'tally_vouchers.id')
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.guid', '=', 'tally_voucher_heads.ledger_guid')
            ->leftJoin('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
            ->whereNot('tally_vouchers.is_optional', 'Yes')
            ->whereIn('tally_ledgers.company_id', $companyIds)
            ->whereIn('tally_vouchers.company_id', $companyIds)
            ->where('tally_ledgers.guid', $cashBankLadgerGuid);

            // dd($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($entry) {
                return Carbon::parse($entry->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('credit', function ($entry) {
                return $entry->entry_type == 'credit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
            })
            ->addColumn('debit', function ($entry) {
                return $entry->entry_type == 'debit' ? number_format(abs($entry->amount), 2, '.', '') : '-';
            })
            ->addColumn('running_balance', function ($entry) {
                // Placeholder for running balance
                return '-';
            })
            ->make(true);
    }


    public function AllVoucherItemReports($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();
    
        $voucherItem = TallyVoucher::whereIn('tally_vouchers.company_id', $companyIds)
            ->where('tally_vouchers.voucher_id', $voucherItemId)
            ->where('tally_vouchers.is_cancelled', 0)
            ->where('tally_vouchers.is_optional', 0)
            ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
            ->select(
                'tally_vouchers.voucher_id',
                'tally_vouchers.voucher_number',
                'tally_vouchers.voucher_date',
                'tally_voucher_types.voucher_type_name'
            )
            ->first();
    
        if (!$voucherItem) {
            abort(404, 'Voucher not found');
        }
    
        $voucherItemIds = [$voucherItem->voucher_id];
    
        $voucherItemName = TallyVoucherHead::join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
            ->whereIn('tally_voucher_heads.voucher_id', $voucherItemIds)
            ->select(
                'tally_voucher_heads.voucher_head_id',
                'tally_voucher_heads.amount',
                'tally_ledgers.ledger_name',
                'tally_ledgers.ledger_id'
            )
            ->get();
    
        $voucherLedgerNames = [];
        foreach ($voucherItemName as $item) {
            $voucherLedgerNames[] = $item->ledger_name;
        }
        $voucherLedgerNames = array_unique($voucherLedgerNames);
    
        $saleReceiptItem = $voucherItem->voucher_type_name;
    
        $voucherHeadsSaleReceipt = TallyVoucherHead::whereIn('voucher_id', $voucherItemIds)->get();

        $bankAcc = TallyVoucherHead::where('tally_voucher_heads.voucher_id', $voucherItemId)
                                    ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                    ->where('tally_ledgers.parent', '!=', 'Bank Accounts')
                                    ->select(
                                        'tally_voucher_heads.voucher_id',
                                        'tally_voucher_heads.amount',
                                        'tally_ledgers.ledger_name',
                                        'tally_ledgers.tax_type'
                                    )
                                    ->get();

        $ledgerData = TallyLedger::whereIn('ledger_name', $voucherLedgerNames)
            ->whereIn('company_id', $companyIds)
            ->get();
    
        $ledgerItem = $ledgerData->first();
    
        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($voucherItem->voucher_date ?? now());
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);
    
        $voucherHeadsName = TallyVoucherHead::whereIn('voucher_id', $voucherItemIds)->get();
        // dd($voucherHeadsName);
        $successfulAllocations = [];
    
        foreach ($voucherHeadsName as $voucherHead) {
            $id = $voucherHead->voucher_head_id;
            // dd($id);
            $bankAllocations = TallyBankAllocation::where('voucher_head_id', $id)->get();
            if ($bankAllocations->isNotEmpty()) {
                $successfulAllocations[] = [
                    'voucher_head'      => $voucherHead,
                    'bank_allocations'  => $bankAllocations,
                ];
            }
        }

        $ledgerIds = [];
        foreach ($voucherItemName as $item) {
            $ledgerIds[] = $item->ledger_id;
        }
        $ledgerIds = array_unique($ledgerIds);
    
        $pendingVoucherHeads = TallyVoucherHead::whereIn('ledger_id', $ledgerIds)->get();
    
        $voucherHeads = TallyVoucherHead::whereIn('voucher_id', $voucherItemIds)->get();
    
        $gstVoucherHeads = TallyVoucherHead::whereIn('voucher_id', $voucherItemIds)
                                            ->where('is_party_ledger', 1)
                                            ->where('entry_type', 'debit')
                                            ->get();
      
        $voucherItems = TallyVoucherItem::where('voucher_item_id', $voucherItemId)->get();
    
        $uniqueGstLedgerSources = [];
        foreach ($voucherItems as $item) {
            $uniqueGstLedgerSources[] = $item->gst_ledger_source;
        }
        $uniqueGstLedgerSources = array_unique($uniqueGstLedgerSources);
    
        $totalCountItems = $voucherItems->count();
        $totalCountHeads = $voucherHeads->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $subtotalsamount = $voucherItems->sum('amount');
    
        $menuItems = TallyVoucher::whereIn('tally_vouchers.company_id', $companyIds)
            ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
            ->where('tally_vouchers.is_cancelled', 0)
            ->where('tally_vouchers.is_optional', 0)
            ->select(
                'tally_vouchers.voucher_id',
                'tally_vouchers.voucher_number',
                'tally_vouchers.voucher_date',
                'tally_voucher_types.voucher_type_name'
            )
            ->get();
    
        $companies = TallyCompany::whereIn('company_id', $companyIds)->get();
    
        return view('app.reports._voucher_items', [
            'companies'               => $companies,
            'voucherItem'             => $voucherItem,
            'voucherItemName'         => $voucherItemName,
            'ledgerData'              => $ledgerData,
            'voucherHeads'            => $voucherHeads,
            'gstVoucherHeads'         => $gstVoucherHeads,
            'totalCountItems'         => $totalCountItems,
            'uniqueGstLedgerSources'  => $uniqueGstLedgerSources,
            'subtotalsamount'         => $subtotalsamount,
            'saleReceiptItem'         => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate'                 => $dueDate,
            'voucherItemId'           => $voucherItemId,
            'menuItems'               => $menuItems,
            'pendingVoucherHeads'     => $pendingVoucherHeads,
            'successfulAllocations'   => $successfulAllocations,
            'totalCountHeads'         => $totalCountHeads,
            'totalCountLinkHeads'     => $totalCountLinkHeads,
            'bankAcc'                 => $bankAcc,
        ]);
    }
    
    public function getVoucherItemData($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_id', $companyIds)
                                    ->findOrFail($voucherItemId);
        $voucherItemName = $voucherItem->party_ledger_name;
        $query = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->get();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }

    public function getVoucherItemTaxData($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_id', $companyIds)
                                        ->findOrFail($voucherItemId);
        $voucherItemName = $voucherItem->party_ledger_name;

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();

        $tallyLedgers = TallyLedger::whereNotNull('gst_duty_head')
                                ->whereIn('company_id', $companyIds)
                                ->where('gst_duty_head', '!=', '')
                                ->get()
                                ->keyBy('name');

        $gstVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($voucherItem, $tallyLedgers) {
            return $voucherHead->ledger_name !== $voucherItem->party_ledger_name &&
                $tallyLedgers->has($voucherHead->ledger_name);
        });

        $query = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->get();
        return DataTables::of($gstVoucherHeads)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }


    public function getVoucherItemReceiptData($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherId = TallyVoucher::whereIn('company_id', $companyIds)
                    ->where('voucher_id', $voucherItemId)
                    ->select('voucher_id')
                    ->firstOrFail();      

        $voucherItem = TallyVoucher::whereIn('tally_vouchers.company_id', $companyIds)
                                    ->whereIn('tally_vouchers.voucher_id', $voucherId)
                                    ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                    ->where('tally_vouchers.is_cancelled', 0)
                                    ->where('tally_vouchers.is_optional', 0)
                                    ->select(
                                        'tally_vouchers.voucher_id',
                                        'tally_vouchers.voucher_number',
                                        'tally_vouchers.voucher_date',
                                        'tally_voucher_types.voucher_type_name'
                                    )
                                    ->get();

        $saleReceiptItem = $voucherItem->firstWhere('voucher_type_name', 'Receipt');

        if (!$saleReceiptItem) {
            return DataTables::of(collect([]))->make(true);
        }
        $receiptItem = $saleReceiptItem->voucher_id;
  
        $query = TallyVoucherHead::where('tally_voucher_heads.voucher_id', $receiptItem)
                                ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                ->where('tally_ledgers.parent', '!=', 'Bank Accounts')
                                // ->whereIn('tally_vouchers.voucher_id', $voucherId)
                                ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                                ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                ->select(
                                    'tally_voucher_heads.voucher_id',
                                    'tally_voucher_heads.amount',
                                    'tally_ledgers.ledger_name',
                                    'tally_vouchers.voucher_number',
                                    'tally_vouchers.voucher_date',
                                    'tally_voucher_types.voucher_type_name'
                                )
                                ->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }


    public function getVoucherItemReceiptInvoiceData($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherId = TallyVoucher::whereIn('company_id', $companyIds)
                    ->where('voucher_id', $voucherItemId)
                    ->select('voucher_id')
                    ->firstOrFail();      

        $voucherItem = TallyVoucher::whereIn('tally_vouchers.company_id', $companyIds)
                                    ->whereIn('tally_vouchers.voucher_id', $voucherId)
                                    ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                                    ->where('tally_vouchers.is_cancelled', 0)
                                    ->where('tally_vouchers.is_optional', 0)
                                    ->select(
                                        'tally_vouchers.voucher_id',
                                        'tally_vouchers.voucher_number',
                                        'tally_vouchers.voucher_date',
                                        'tally_voucher_types.voucher_type_name'
                                    )
                                    ->get();

        $saleReceiptItem = $voucherItem->firstWhere('voucher_type_name', 'Receipt');

        if (!$saleReceiptItem) {
            return DataTables::of(collect([]))->make(true);
        }
        $receiptItem = $saleReceiptItem->voucher_id;
  
        $query = TallyVoucherHead::where('tally_voucher_heads.voucher_id', $receiptItem)
                                ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                ->where('tally_ledgers.parent', '!=', 'Bank Accounts')
                                ->select(
                                    'tally_voucher_heads.voucher_id',
                                    'tally_voucher_heads.amount',
                                    'tally_ledgers.ledger_name'
                                )
                                ->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }

    public function AllVoucherItemPaymentReports($voucherItemId)
    {
        $companyIds = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_id', $companyIds)
                                    ->findOrFail($voucherItemId);

        $voucherItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)
                                        ->whereNot('tally_vouchers.is_cancelled', 0)
                                        ->whereNot('tally_vouchers.is_optional', 0)
                                        ->whereIn('company_id', $companyIds)
                                        ->get();

        $saleReceiptItem = $voucherItemName->filter(function ($item) use ($voucherItem) {
            return $item->voucher_type !== $voucherItem->voucher_type;
        })->first();

        if ($saleReceiptItem) {
            $voucherHeadsSaleReceipt = TallyVoucherHead::where('tally_voucher_id', $saleReceiptItem->id)
                ->where('ledger_name', $saleReceiptItem->party_ledger_name)
                ->get();
        } else {
            $voucherHeadsSaleReceipt = collect();
        }

        $ledgerData = TallyLedger::where('name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        if ($ledgerData instanceof \Illuminate\Support\Collection) {
            $ledgerItem = $ledgerData->first();
        } else {
            $ledgerItem = $ledgerData;
        }

        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($voucherItem->voucher_date);
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();

        $gstVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($voucherItem) {
            return $voucherHead->ledger_name !== $voucherItem->party_ledger_name;
        });


        $voucherHeadsName = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();
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
            // dd($successfulAllocations);


        $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $voucherItem->party_ledger_name)->get();
        // dd($voucherHeadsSaleReceipt);

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();
        $totalRoundOff = $voucherHeads->filter(function ($head) {
            return $head->ledger_name === 'Round Off';
        })->sum('amount');
        $totalIGST18 = $voucherHeads->filter(function ($head) {
            return $head->ledger_name === 'IGST @18%';
        })->sum('amount');

        $voucherItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->get();
        $uniqueGstLedgerSources = $voucherItems->pluck('gst_ledger_source')->unique();
        $totalCountItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $subtotalsamount = $voucherItems->sum('amount');

        $menuItems = TallyVoucher::where('voucher_type', 'Payment')
                                ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                                ->whereNot('tally_vouchers.is_optional', 'Yes')
                                ->whereIn('company_guid', $companyGuids)
                                ->get();

        return view('app.reports._voucher_payment_items', [
            'voucherItem' => $voucherItem,
            'ledgerData' => $ledgerData,
            'voucherHeads' => $voucherHeads,
            'totalRoundOff' => $totalRoundOff,
            'totalIGST18' => $totalIGST18,
            'totalCountItems' => $totalCountItems,
            'uniqueGstLedgerSources' => $uniqueGstLedgerSources,
            'subtotalsamount' => $subtotalsamount,
            'saleReceiptItem' => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate' => $dueDate,
            'voucherItemId' => $voucherItemId,
            'menuItems' => $menuItems,
            'gstVoucherHeads' => $gstVoucherHeads,
            'pendingVoucherHeads' => $pendingVoucherHeads,
            'successfulAllocations' => $successfulAllocations,
            'totalCountHeads' => $totalCountHeads,
            'totalCountLinkHeads' => $totalCountLinkHeads
        ]);
    }


    public function AllVoucherItemReceiptReports($voucherItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($voucherItemId);

        $voucherItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)
                                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                                        ->whereIn('company_guid', $companyGuids)
                                        ->get();

        $saleReceiptItem = $voucherItemName->filter(function ($item) use ($voucherItem) {
            return $item->voucher_type !== $voucherItem->voucher_type;
        })->first();

        // Check if $saleReceiptItem is not null
        if ($saleReceiptItem) {
            $voucherHeadsSaleReceipt = TallyVoucherHead::where('tally_voucher_id', $saleReceiptItem->id)
            ->where('ledger_name', $saleReceiptItem->party_ledger_name)
                ->get();
        } else {
            $voucherHeadsSaleReceipt = collect();
        }

        $ledgerData = TallyLedger::where('name', $voucherItem->party_ledger_name)->get();
        if ($ledgerData instanceof \Illuminate\Support\Collection) {
            $ledgerItem = $ledgerData->first();
        } else {
            $ledgerItem = $ledgerData;
        }

        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($voucherItem->voucher_date);
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);


        $voucherHeadsName = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();
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
            // dd($successfulAllocations);
        $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $voucherItem->party_ledger_name)->get();
        // dd($pendingVoucherHeads);



        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();
        $totalRoundOff = $voucherHeads->filter(function ($head) {
            return $head->ledger_name === 'Round Off';
        })->sum('amount');
        $totalIGST18 = $voucherHeads->filter(function ($head) {
            return $head->ledger_name === 'IGST @18%';
        })->sum('amount');

        $voucherItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->get();
        $uniqueGstLedgerSources = $voucherItems->pluck('gst_ledger_source')->unique();
        $totalCountItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $subtotalsamount = $voucherItems->sum('amount');

        $menuItems = TallyVoucher::where('voucher_type', 'Receipt')
                                ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                                ->whereNot('tally_vouchers.is_optional', 'Yes')
                                ->whereIn('company_guid', $companyGuids)
                                ->get();

        return view('app.reports._voucher_receipt_items', [
            'voucherItem' => $voucherItem,
            'ledgerData' => $ledgerData,
            'voucherHeads' => $voucherHeads,
            'totalRoundOff' => $totalRoundOff,
            'totalIGST18' => $totalIGST18,
            'totalCountItems' => $totalCountItems,
            'uniqueGstLedgerSources' => $uniqueGstLedgerSources,
            'subtotalsamount' => $subtotalsamount,
            'saleReceiptItem' => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate' => $dueDate,
            'voucherItemId' => $voucherItemId,
            'menuItems' => $menuItems,
            'pendingVoucherHeads' => $pendingVoucherHeads,
            'successfulAllocations' => $successfulAllocations,
            'totalCountHeads' => $totalCountHeads,
            'totalCountLinkHeads' => $totalCountLinkHeads
        ]);
    }
}
