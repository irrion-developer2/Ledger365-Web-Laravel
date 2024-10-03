<?php

namespace App\Http\Controllers\SuperAdmin\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Services\ReportService;
use App\Models\TallyLedger;
use App\Models\TallyGroup;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherAccAllocationHead;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportBalanceSheetController extends Controller
{

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $companyGuids = $this->reportService->companyData();

        $company = TallyCompany::where('guid', $companyGuids)->first();

        return view ('superadmin.reports.balanceSheet.index', compact('company'));
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {

            $query = TallyGroup::where(function($query) {
                $query->where('parent', '')
                      ->orWhereNull('parent');
            })->whereIn('company_guid', $companyGuids);
            

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('account_type', function ($data) {
                    $name = strtolower($data->name);
                    if (strpos($name, 'assets') !== false || strpos($name, 'asset') !== false || strpos($name, 'investments') !== false) {
                        return 'Asset';
                    } elseif (strpos($name, 'income') !== false || strpos($name, 'revenue') !== false || strpos($name, 'sales accounts') !== false) {
                        return 'Revenue';
                    } elseif (strpos($name, 'liabilities') !== false || strpos($name, 'liability') !== false || strpos($name, 'branch / divisions') !== false || strpos($name, 'suspense a/c') !== false || strpos($name, 'capital account') !== false) {
                        return 'Liability';
                    } elseif (strpos($name, 'expense') !== false || strpos($name, 'purchase') !== false) {
                        return 'Expense';
                    }
                    return $data->account_type;
                })
                ->editColumn('amount', function ($data) use ($companyGuids){
                    $name = $data->name;

                    foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
                        if (strpos($name, $pattern) !== false) {
                            $name = $normalized;
                            break;
                        }
                    }

                    $groupLedgerIdsQuery = TallyGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
                    $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');

                    if ($groupLedgerIds->isNotEmpty()) {
                        $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    } else {
                        $ledgerIds = TallyLedger::where('parent', $name)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    }

                    $allLedgerIds = $ledgerIds->unique();

                    if ($allLedgerIds->isEmpty()) {
                        return '-';
                    }

                    $totalDebitHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                                        ->where('tally_voucher_heads.entry_type', 'debit')
                                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                        ->sum('tally_voucher_heads.amount');



                    $totalDebitBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                                            ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                                                                            ->where('tally_voucher_acc_allocation_heads.entry_type', 'debit')
                                                                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                                            ->sum('tally_voucher_acc_allocation_heads.amount');

                    $totalDebit = $totalDebitHead + $totalDebitBankHead;

                    $totalCreditHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                        ->where('tally_voucher_heads.entry_type', 'credit')
                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                        ->sum('tally_voucher_heads.amount');



                    $totalCreditBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                        ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                        ->where('tally_voucher_acc_allocation_heads.entry_type', 'credit')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->sum('tally_voucher_acc_allocation_heads.amount');

                    $totalCredit = $totalCreditHead + $totalCreditBankHead;


                    $openingBalance = TallyVoucherHead::whereIn('ledger_guid', $allLedgerIds)
                        ->where('entry_type', 'opening')
                        ->sum('amount');

                    $total = $totalDebit + $totalCredit;

                    $closingBalance = $openingBalance + $total;

                    if ($closingBalance == 0) {
                        return '-';
                    }

                    return number_format(abs($closingBalance), 3);
                })
                ->editColumn('AssetAmount', function ($data) use ($companyGuids){
                    $name = $data->name;

                    foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
                        if (strpos($name, $pattern) !== false) {
                            $name = $normalized;
                            break;
                        }
                    }

                    $groupLedgerIdsQuery = TallyGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
                    $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');

                    if ($groupLedgerIds->isNotEmpty()) {
                        $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    } else {
                        $ledgerIds = TallyLedger::where('parent', $name)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    }

                    $allLedgerIds = $ledgerIds->unique();

                    if ($allLedgerIds->isEmpty()) {
                        return '-';
                    }

                    $totalDebitHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                                        ->where('tally_voucher_heads.entry_type', 'debit')
                                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                        ->sum('tally_voucher_heads.amount');



                    $totalDebitBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                                        ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                                                                        ->where('tally_voucher_acc_allocation_heads.entry_type', 'debit')
                                                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                                        ->sum('tally_voucher_acc_allocation_heads.amount');


                    $totalCreditHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                        ->where('tally_voucher_heads.entry_type', 'credit')
                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                        ->sum('tally_voucher_heads.amount');



                    $totalCreditBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                        ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                        ->where('tally_voucher_acc_allocation_heads.entry_type', 'credit')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->sum('tally_voucher_acc_allocation_heads.amount');


                    $stockItemValue = $this->reportService->calculateStockValue();
                    $stockItemValue = str_replace(',', '', $stockItemValue);
                    // dd($stockItemValue);

                    $totalDebit = $totalDebitHead + $totalDebitBankHead;

                    $totalCredit = $totalCreditHead + $totalCreditBankHead;

                    $totalItemValue = $totalDebit + $stockItemValue;
                    // dd($totalCredit);

                    $openingBalance = TallyVoucherHead::whereIn('ledger_guid', $allLedgerIds)
                        ->where('entry_type', 'opening')
                        ->sum('amount');

                    $total = $totalDebit + $totalCredit;

                    $closingBalance = $openingBalance + $total;

                    if ($closingBalance == 0) {
                        return '-';
                    }

                    return number_format(abs($closingBalance), 3);
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'liabilities') !== false || strpos($name, 'liability') !== false;
                    });
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'assets') !== false || strpos($name, 'asset') !== false;
                    });
                })

                ->make(true);
        }
    }

    public function getAssetData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {

            $query = TallyGroup::select('parent')
            ->whereIn('parent', ['Current Assets'])
            ->whereIn('company_guid', $companyGuids)
            ->groupBy('parent');


            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('amount', function ($data) use ($companyGuids){
                    $name = $data->name;

                    foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
                        if (strpos($name, $pattern) !== false) {
                            $name = $normalized;
                            break;
                        }
                    }

                    $groupLedgerIdsQuery = TallyGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
                    $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');

                    if ($groupLedgerIds->isNotEmpty()) {
                        $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    } else {
                        $ledgerIds = TallyLedger::where('parent', $name)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    }

                    $allLedgerIds = $ledgerIds->unique();

                    if ($allLedgerIds->isEmpty()) {
                        return '-';
                    }

                    $totalDebitHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                                        ->where('tally_voucher_heads.entry_type', 'debit')
                                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                        ->sum('tally_voucher_heads.amount');



                    $totalDebitBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                                            ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                                                                            ->where('tally_voucher_acc_allocation_heads.entry_type', 'debit')
                                                                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                                                            ->sum('tally_voucher_acc_allocation_heads.amount');

                    $totalDebit = $totalDebitHead + $totalDebitBankHead;

                    $totalCreditHead = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                        ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds) // Specify table name to avoid ambiguity
                                        ->where('tally_voucher_heads.entry_type', 'credit')
                                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                                        ->sum('tally_voucher_heads.amount');



                    $totalCreditBankHead = TallyVoucherAccAllocationHead::join('tally_vouchers', 'tally_voucher_acc_allocation_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                        ->whereIn('tally_voucher_acc_allocation_heads.ledger_guid', $allLedgerIds)
                        ->where('tally_voucher_acc_allocation_heads.entry_type', 'credit')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->sum('tally_voucher_acc_allocation_heads.amount');

                    $totalCredit = $totalCreditHead + $totalCreditBankHead;


                    $openingBalance = TallyVoucherHead::whereIn('ledger_guid', $allLedgerIds)
                        ->where('entry_type', 'opening')
                        ->sum('amount');

                    $total = $totalDebit + $totalCredit;

                    $closingBalance = $openingBalance + $total;

                    if ($closingBalance == 0) {
                        return '-';
                    }

                    return number_format(abs($closingBalance), 3);
                })
                ->make(true);
        }
    }

}
