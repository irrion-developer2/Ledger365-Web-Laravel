<?php

namespace App\Http\Controllers\SuperAdmin\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyGroup;
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
        // 1. Try to get companyId from the session (previous URL)
        $previousUrl = $request->session()->get('_previous.url');
        $companyId = null;

        if ($previousUrl) {
            // Attempt to extract the companyId from the URL if available
            $urlParts = explode('/', $previousUrl);
            $companyId = end($urlParts);

            // Log the extracted value from the previous URL
            \Log::info('Company ID from Previous URL:', ['companyId' => $companyId]);

            // Check if the extracted value is a valid numeric ID
            if (!is_numeric($companyId)) {
                $companyId = null;
                \Log::info('Invalid Company ID from Previous URL (Non-numeric)', ['previousUrl' => $previousUrl]);
            }
        } else {
            \Log::info('No Previous URL found in session');
        }

        // 2. Fallback: Try to get companyId from the current request (route parameter or query string)
        if (!$companyId) {
            $companyId = $request->route('companyId'); // Assuming companyId is part of the route
            \Log::info('Company ID from Route:', ['companyId' => $companyId]);
        }

        // 3. Fallback: Try to get companyId from query string (e.g., ?companyId=123)
        if (!$companyId && $request->has('companyId')) {
            $companyId = $request->query('companyId');
            \Log::info('Company ID from Query String:', ['companyId' => $companyId]);
        }

        // 4. Fallback: Try to get companyId from session storage (other session keys)
        if (!$companyId) {
            $companyId = $request->session()->get('companyId');
            \Log::info('Company ID from Session (other session keys):', ['companyId' => $companyId]);
        }

        // 5. Final check: If no companyId is found or it's not valid, set a default value or return an error
        if (!is_numeric($companyId)) {
            \Log::error('Company ID is missing or invalid.');
            return redirect()->back()->withErrors(['message' => 'Company ID is missing or invalid']);
        }

        // Retrieve company data from the database
        $company = TallyCompany::find($companyId);

        if (!$company) {
            \Log::error('Company not found for Company ID:', ['companyId' => $companyId]);
            return redirect()->back()->withErrors(['message' => 'Company not found']);
        }

        \Log::info('Company successfully retrieved:', ['companyId' => $companyId, 'companyName' => $company->name]);

        // Pass the company data to the view
        return view('superadmin.reports.index', compact('company'));
    }


    public function AllVoucherHeadReports($voucherHeadId)
    {
        $companyGuids = $this->reportService->companyData();

        $voucherHead = TallyLedger::whereIn('company_guid', $companyGuids)->where('guid', $voucherHeadId)->firstOrFail();
        $voucherHeadName = $voucherHead->language_name;

        $menuItems = TallyLedger::where('language_name', $voucherHead->language_name)->whereIn('company_guid', $companyGuids)->get();

        return view('superadmin.reports._voucher_heads', [
            'voucherHead' => $voucherHead,
            'voucherHeadId' => $voucherHeadId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getVoucherHeadData($cashBankLedgerId)
    {
        $companyGuids = $this->reportService->companyData();

        $cashBankLedger = TallyLedger::whereIn('company_guid', $companyGuids)->where('guid', $cashBankLedgerId)->firstOrFail();
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
            ->whereIn('tally_ledgers.company_guid', $companyGuids)
            ->whereIn('tally_vouchers.company_guid', $companyGuids)
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
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                ->findOrFail($voucherItemId);

        $voucherItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        // $saleReceiptItem = $voucherItemName->firstWhere('voucher_type', 'Receipt');

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
        // dd($saleReceiptItem);


        $bankAccItem = $voucherItemName->firstWhere('voucher_type', 'Receipt');

        if ($bankAccItem) {
            $bankAccreceiptItem = $bankAccItem->id; // Only assign id if $bankAccItem is not null
            $bankAcc = TallyVoucherHead::where('tally_voucher_id', $bankAccreceiptItem)
                ->whereHas('ledger', function($q) {
                    $q->where('parent', 'Bank Accounts');
                })
                ->with('ledger') // Eager load the ledger relationship
                ->get();
        } else {
            $bankAcc = collect(); // Return an empty collection if no bank account item is found
        }


        // dd($bankAcc);


        $ledgerData = TallyLedger::where('language_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        if ($ledgerData instanceof \Illuminate\Support\Collection) {
            $ledgerItem = $ledgerData->first();
        } else {
            $ledgerItem = $ledgerData;
        }
        // dd($ledgerItem);

        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($voucherItem->voucher_date);
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);
        // dd($dueDate);

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
        $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $voucherItem->party_ledger_name)->get();

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();

        $gstVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($voucherItem) {
            return $voucherHead->ledger_name !== $voucherItem->party_ledger_name;
        });

        $voucherItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->get();
        $uniqueGstLedgerSources = $voucherItems->pluck('gst_ledger_source')->unique();
        $totalCountItems = TallyVoucherItem::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $subtotalsamount = $voucherItems->sum('amount');

        $menuItems = TallyVoucher::whereIn('company_guid', $companyGuids)->get();

        $voucherItemGuid = $voucherItem->company_guid;
        $companies = TallyCompany::where('guid', $voucherItemGuid)->get();

        return view('superadmin.reports._voucher_items', [
            'companies'  => $companies,
            'voucherItem' => $voucherItem,
            'ledgerData' => $ledgerData,
            'voucherHeads' => $voucherHeads,
            'gstVoucherHeads' => $gstVoucherHeads,
            'totalCountItems' => $totalCountItems,
            'uniqueGstLedgerSources' => $uniqueGstLedgerSources,
            'subtotalsamount' => $subtotalsamount,
            'saleReceiptItem' => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate' => $dueDate,
            'voucherItemId' => $voucherItemId ,
            'menuItems' => $menuItems,
            'pendingVoucherHeads' => $pendingVoucherHeads,
            'successfulAllocations' => $successfulAllocations,
            'totalCountHeads' => $totalCountHeads,
            'totalCountLinkHeads' => $totalCountLinkHeads,
            'bankAcc' => $bankAcc
        ]);
    }


    public function getVoucherItemData($voucherItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
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
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                        ->findOrFail($voucherItemId);
        $voucherItemName = $voucherItem->party_ledger_name;

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $voucherItemId)->get();

        $tallyLedgers = TallyLedger::whereNotNull('gst_duty_head')
                                ->whereIn('company_guid', $companyGuids)
                                ->where('gst_duty_head', '!=', '')
                                ->get()
                                ->keyBy('language_name');

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
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                        ->findOrFail($voucherItemId);

        $saleItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        $saleReceiptItem = $saleItemName->firstWhere('voucher_type', 'Receipt');

        if (!$saleReceiptItem) {
            return DataTables::of(collect([]))->make(true);
        }
        $receiptItem = $saleReceiptItem->id;

        $query = TallyVoucher::where('id', $receiptItem)->whereIn('company_guid', $companyGuids)->get();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('amount', function ($data) {
                $ledgerIds = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    //  ->where('entry_type', "credit")
                    ->where('ledger_name', $data->party_ledger_name)
                    ->pluck('id');

                $totalSales = TallyVoucherHead::whereIn('id', $ledgerIds)
                    ->sum('amount');

                return $totalSales;
            })
            ->make(true);
    }

    public function getVoucherItemReceiptInvoiceData($voucherItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($voucherItemId);

        $saleItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        $saleReceiptItem = $saleItemName->firstWhere('voucher_type', 'Receipt');


        if (!$saleReceiptItem) {
            return DataTables::of(collect([]))->make(true);
        }
        $receiptItem = $saleReceiptItem->id;

        // $query = TallyVoucherHead::where('tally_voucher_id', $receiptItem)
        // ->get();

        $query = TallyVoucherHead::where('tally_voucher_id', $receiptItem)
        ->whereHas('ledger', function($q) {
            $q->where('parent', '!=', 'Bank Accounts');
        })
        ->get();

        // dd($query);
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }

    public function AllVoucherItemPaymentReports($voucherItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $voucherItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($voucherItemId);

        $voucherItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();

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

        $ledgerData = TallyLedger::where('language_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
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

        $menuItems = TallyVoucher::where('voucher_type', 'Payment')->whereIn('company_guid', $companyGuids)->get();

        return view('superadmin.reports._voucher_payment_items', [
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

        $voucherItemName = TallyVoucher::where('party_ledger_name', $voucherItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();

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

        $ledgerData = TallyLedger::where('language_name', $voucherItem->party_ledger_name)->get();
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

        $menuItems = TallyVoucher::where('voucher_type', 'Receipt')->whereIn('company_guid', $companyGuids)->get();

        return view('superadmin.reports._voucher_receipt_items', [
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
