<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use App\Models\TallyLedgerGroup;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class ReportCustomerGroupController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.reports.customerGroup.index');
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $query = TallyLedgerGroup::whereIn('company_guid', $companyGuids)->where('name', 'Sundry Debtors');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('total_sales', function ($data) use ($companyGuids) {

                    $salesAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($data, $companyGuids) {
                        $query->join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                            ->where('tally_ledgers.parent', $data->name)
                            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                            ->whereNot('tally_vouchers.is_optional', 'Yes')
                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                            ->where('tally_vouchers.voucher_type', 'sales');
                    })->where('entry_type', 'debit')
                    ->sum('amount');

                    
                    $creditAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($data, $companyGuids) {
                        $query->join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                            ->where('tally_ledgers.parent', $data->name)
                            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                            ->whereNot('tally_vouchers.is_optional', 'Yes')
                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                            ->where('tally_vouchers.voucher_type', 'credit note');
                    })
                    ->sum('amount');
                    
            
                    $Amt = $salesAmt + $creditAmt;

                    return number_format(abs($Amt), 2); 
                })
                ->addColumn('transaction', function ($data) use ($companyGuids) {
                    $salesCount = TallyVoucher::join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                        ->where('tally_ledgers.parent', $data->name)
                        ->where('tally_vouchers.voucher_type', 'sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->count();

                    $creditCount = TallyVoucher::join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                        ->where('tally_ledgers.parent', $data->name)
                        ->where('tally_vouchers.voucher_type', 'credit note')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->count();

                    $transaction = $salesCount + $creditCount;

                    return number_format($transaction, 2);
                })
                ->addColumn('avg_sales', function ($data) use ($companyGuids) {
                    $salesAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($data, $companyGuids) {
                        $query->join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                            ->where('tally_ledgers.parent', $data->name)
                            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                            ->whereNot('tally_vouchers.is_optional', 'Yes')
                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                            ->where('tally_vouchers.voucher_type', 'sales');
                    })->where('entry_type', 'debit')
                    ->sum('amount');
            
                    $creditAmt = TallyVoucherHead::whereHas('voucher', function ($query) use ($data, $companyGuids) {
                        $query->join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                            ->where('tally_ledgers.parent', $data->name)
                            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                            ->whereNot('tally_vouchers.is_optional', 'Yes')
                            ->whereIn('tally_vouchers.company_guid', $companyGuids)
                            ->where('tally_vouchers.voucher_type', 'credit note');
                    })
                    ->sum('amount');
            
                    $totalAmount = $salesAmt + $creditAmt;

                    $salesCount = TallyVoucher::join('tally_ledgers', 'tally_vouchers.party_ledger_guid', '=', 'tally_ledgers.guid')
                        ->where('tally_ledgers.parent', $data->name)
                        ->where('tally_vouchers.voucher_type', 'sales')
                        ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                        ->whereNot('tally_vouchers.is_optional', 'Yes')
                        ->whereIn('tally_vouchers.company_guid', $companyGuids)
                        ->count();

                    $avgSales = $salesCount > 0 ? $totalAmount / $salesCount : 0; // Avoid division by zero

                    return number_format(abs($avgSales), 2);
                })
                ->make(true);
        }
    }

    public function AllCustomerGroupLedgerReports($customerGroupLedgerId)
    {
        $companyGuids = $this->reportService->companyData();

        $customerGroupLedger = TallyLedgerGroup::whereIn('company_guid', $companyGuids)
                                        ->findOrFail($customerGroupLedgerId);

        $menuItems = TallyLedgerGroup::where('name', $customerGroupLedger->name)->whereIn('company_guid', $companyGuids)->get();

        return view('app.reports.customerGroup._customer_group_ledger', [
            'customerGroupLedger' => $customerGroupLedger,
            'customerGroupLedgerId' => $customerGroupLedgerId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getCustomerGroupLedgerData($customerGroupLedgerId)
    {
        $companyGuids = $this->reportService->companyData();

        $generalLedger = TallyLedgerGroup::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($customerGroupLedgerId);
        $generalLedgerName = $generalLedger->name;
    
        $normalizedNames = [
            'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
            'Direct Incomes, Income (Direct)' => 'Direct Incomes',
            'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
            'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
        ];
    
        if (array_key_exists($generalLedgerName, $normalizedNames)) {
            $generalLedgerName = $normalizedNames[$generalLedgerName];
        }
    
        $query = TallyLedger::where('parent', $generalLedgerName)
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.guid', '=', 'tally_voucher_heads.ledger_guid')
            ->leftJoin('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
            ->select(
                'tally_ledgers.language_name',
                'tally_ledgers.guid',
                'tally_ledgers.opening_balance',
                DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as debit'),
                DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as credit'),
                DB::raw('(tally_ledgers.opening_balance + 
                        SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) - 
                        SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END)) as closing_balance'),
                DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) as sales_amount'),
                DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END) as credit_note_amount'),
                DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN 1 ELSE 0 END) as sales_count'),
                DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN 1 ELSE 0 END) as credit_note_count'),
                DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) - 
                SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END) as total_sales'),
                DB::raw('100 * SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) / 
                NULLIF(SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) - 
                SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END), 0) as percentage')
            )
            ->whereIn('tally_ledgers.company_guid', $companyGuids)
            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
            ->whereNot('tally_vouchers.is_optional', 'Yes')
            ->groupBy('tally_ledgers.language_name', 'tally_ledgers.guid', 'tally_ledgers.opening_balance');

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->editColumn('percentage', function ($row) {
                            return $this->formatNumber($row->percentage) . '%';
                        })
                    ->editColumn('opening_balance', function ($row) {
                        return $this->formatNumber($row->opening_balance);
                    })
                    ->editColumn('total_sales', function ($row) {
                        $totalSales = $row->sales_amount + $row->credit_note_amount;
                        return $this->formatNumber($totalSales);
                    })
                    ->editColumn('debit', function ($row) {
                        return $this->formatNumber($row->debit);
                    })
                    ->editColumn('credit', function ($row) {
                        return $this->formatNumber($row->credit);
                    })
                    ->editColumn('closing_balance', function ($row) {
                        return $this->formatNumber($row->closing_balance);
                    })
                    ->editColumn('sales_count', function ($row) {
                        return $row->sales_count;
                    })
                    ->editColumn('credit_note_count', function ($row) {
                        return $row->credit_note_count;
                    })
                    ->editColumn('total_count', function ($row) {
                        return $row->sales_count + $row->credit_note_count;
                    })
                    // ->editColumn('avg_sales', function ($row) {
                    //     $totalSales = $row->sales_amount + $row->credit_note_amount;
                    //     $avgSales = $totalSales / $row->sales_count; 
                    //     return $this->formatNumber($avgSales);
                    // }) 
                    ->editColumn('avg_sales', function ($row) {
                        $totalSales = $row->sales_amount + $row->credit_note_amount;
                    
                        if ($row->sales_count == 0) {
                            return $this->formatNumber(0);
                        }
                    
                        $avgSales = $totalSales / $row->sales_count;
                        return $this->formatNumber($avgSales);
                    })
                    
                    ->filterColumn('language_name', function ($query, $keyword) {
                        $query->where('tally_ledgers.language_name', 'like', "%{$keyword}%");
                    })
                    ->filterColumn('total_sales', function ($query, $keyword) {
                       
                    })
                    ->filterColumn('total_count', function ($query, $keyword) {
                        
                    })
                    ->filterColumn('avg_sales', function ($query, $keyword) {
                        
                    })
                    ->filterColumn('percentage', function ($query, $keyword) {
                        // Filtering logic for derived columns is not supported in SQL. You can handle it in PHP.
                    })
                    ->orderColumn('total_count', function($row, $order) {
                        $direction = $order === 'desc' ? 'desc' : 'asc';
                        
                    })
                    ->orderColumn('avg_sales', function($row, $order) {
                        $direction = $order === 'desc' ? 'desc' : 'asc';
                        
                    })
                ->make(true);
    }

    protected function formatNumber($value)
    {
        if (!is_numeric($value) || $value == 0) {
            return '-';
        }

        $floatValue = (float) $value;
        return number_format(abs($floatValue), 2, '.', '');
    }

}
