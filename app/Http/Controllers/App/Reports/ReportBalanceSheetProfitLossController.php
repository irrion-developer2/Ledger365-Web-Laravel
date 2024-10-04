<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportBalanceSheetProfitLossController extends Controller
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

        return view ('app.reports.balanceSheet.profitLoss.index', compact('company'));
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $openingValueSum = TallyItem::whereIn('company_guid', $companyGuids)->sum('opening_value');
            $data = [
                [
                    'name' => 'Opening Stock',
                    'opening_value' => number_format(abs($openingValueSum), 3)
                ]
            ];
            return response()->json(['data' => $data]);
        }
    }

    public function getExpenseData(Request $request)
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
                        ->where('entry_type','opening')
                        ->sum('amount');

                    $total = $totalDebit + $totalCredit;

                    $closingBalance = $openingBalance + $total;

                        if ($closingBalance == 0) {
                        return '                    -';
                    }

                    return number_format(abs($closingBalance), 3);
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'expense') !== false || strpos($name, 'purchase') !== false;
                    });
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'income') !== false || strpos($name, 'revenue') !== false;
                    });
                })

                ->make(true);
        }
    }

    public function getClosingStockData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $tallyItems = TallyItem::with('tallyVoucherItems')->whereIn('company_guid', $companyGuids)->get();
            $closingValueSum = 0;

            foreach ($tallyItems as $entry) {
                $stockOnHandBalance = 0;
                $openingBalance = 0;
                $stockOnHandValue = 0;

                $openingBalance = $this->reportService->extractNumericValue($entry->opening_balance);
                $openingValue = $this->reportService->extractNumericValue($entry->opening_value);

                $stockItemData = $this->reportService->calculateStockItemVoucherBalance($entry->name);
                $stockItemVoucherPurchaseBalance = $stockItemData['purchase_qty'];
                            $stockItemVoucherDebitNoteBalance = $stockItemData['debit_note_qty'];
                $stockItemVoucherHandBalance = $stockItemData['balance'];


                $stockAmountData = $this->reportService->calculateStockItemVoucherAmount($entry->name);
                $stockItemVoucherPurchaseAmount = $stockAmountData['purchase_amt'];
                            $stockItemVoucherDebitNoteAmount = $stockAmountData['debit_note_amt'];


                $openingAmount = $stockItemVoucherPurchaseAmount + $stockItemVoucherDebitNoteAmount;
                $finalOpeningValue = $openingValue - $openingAmount;
                $finalOpeningBalance = $openingBalance + $stockItemVoucherPurchaseBalance - $stockItemVoucherDebitNoteBalance;

                if ($openingBalance == 0) {
                    $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                } else {
                    $stockItemVoucherSaleValue = $finalOpeningValue / $finalOpeningBalance;
                    $stockItemVoucherSaleValue = number_format($stockItemVoucherSaleValue, 4, '.', '');
                    $stockOnHandBalance = $openingBalance - $stockItemVoucherHandBalance;
                }

                $stockOnHandValue = $stockItemVoucherSaleValue * $stockOnHandBalance;
                 $closingValueSum += $stockOnHandValue;
            }

            $data = [
                [
                    'name' => 'Closing Stock',
                    'closing_value' => $this->reportService->calculateStockValue()
                ]
             ];

            return response()->json(['data' => $data]);
        }
    }
}
