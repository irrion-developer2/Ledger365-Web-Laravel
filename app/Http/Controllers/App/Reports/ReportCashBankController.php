<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use App\Models\TallyGroup;
use App\Models\TallyLedger;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB;
use App\DataTables\SuperAdmin\CashBankDataTable;
use App\Services\ReportService;

class ReportCashBankController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    protected function formatNumber($value)
    {
        // Ensure the value is numeric
        if (!is_numeric($value) || $value == 0) {
            return '-';
        }
    
        // Convert to float, remove negative sign, and format
        $floatValue = (float) $value;
        return number_format(abs($floatValue), 2, '.', '');
    }
    

    public function index(CashBankDataTable $dataTable)
    {
        return $dataTable->render('app.reports.cashBank.index');
    }

    public function AllCashBankReports($cashBankId)
    {
        $companyGuids = $this->reportService->companyData();

        $cashBank = TallyGroup::whereIn('company_guid', $companyGuids)
                                ->findOrFail($cashBankId);

        $names = ['Bank Accounts', 'Bank OD A/c, Bank OCC A/c', 'Cash-in-Hand'];
        $menuItems = TallyGroup::whereIn('name', $names)->whereIn('company_guid', $companyGuids)->get();
        // dd($menuItems);

        return view('app.reports.cashBank._cash_bank_details', [
            'cashBank' => $cashBank,
            'cashBankId' => $cashBankId ,
            'menuItems' => $menuItems
        ]);
    }


    // public function getCashBankData($cashBankId)
    // {
    //     $cashBank = TallyGroup::findOrFail($cashBankId);
    //     $cashBankName = $cashBank->name;

    //     $query = TallyLedger::where('parent', $cashBankName);

    //     return DataTables::of($query)
    //         ->addIndexColumn()
    //         ->editColumn('created_at', function ($request) {
    //             return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
    //         })
    //         ->make(true);
    // }
   
    public function getCashBankData($cashBankId)
    {
        $companyGuids = $this->reportService->companyData();

        $cashBank = TallyGroup::whereIn('company_guid', $companyGuids)
                                ->findOrFail($cashBankId);
        $cashBankName = $cashBank->name;

        // Define your normalized names array
        $normalizedNames = [
            'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
            'Direct Incomes, Income (Direct)' => 'Direct Incomes',
            'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
            'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
        ];

        // Check if the generalLedgerName is in the normalized names array
        if (array_key_exists($cashBankName, $normalizedNames)) {
            $cashBankName = $normalizedNames[$cashBankName];
        }

        $query = TallyLedger::where('parent', $cashBankName)
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.guid', '=', 'tally_voucher_heads.ledger_guid')
            ->leftJoin('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
            ->select(
                'tally_ledgers.language_name',
                'tally_ledgers.guid',
                'tally_ledgers.opening_balance',
                DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as debit'),
                DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as credit'),
                DB::raw('(tally_ledgers.opening_balance + 
                        SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) + 
                        SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END)) as closing_balance')
            )
            // ->whereIn('tally_ledgers.company_guid', $companyGuids)
            ->whereIn('tally_vouchers.company_guid', $companyGuids) // Corrected here
            ->groupBy('tally_ledgers.language_name', 'tally_ledgers.guid', 'tally_ledgers.opening_balance');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('opening_balance', function ($row) {
                return $this->formatNumber($row->opening_balance);
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
            ->make(true);
    }


    // public function getCashBankData($cashBankId)
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     $cashBank = TallyGroup::whereIn('company_guid', $companyGuids)
    //                             ->findOrFail($cashBankId);
    //     $cashBankName = $cashBank->name;

    //     // Define your normalized names array
    //     $normalizedNames = [
    //         'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
    //         'Direct Incomes, Income (Direct)' => 'Direct Incomes',
    //         'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
    //         'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
    //     ];

    //     // Check if the generalLedgerName is in the normalized names array
    //     if (array_key_exists($cashBankName, $normalizedNames)) {
    //         $cashBankName = $normalizedNames[$cashBankName];
    //     }

    //     // dd()
    //     $query = TallyLedger::where('parent', $cashBankName)
    //         ->leftJoin('tally_voucher_heads', 'tally_ledgers.guid', '=', 'tally_voucher_heads.ledger_guid')
    //         ->select(
    //             'tally_ledgers.language_name',
    //             'tally_ledgers.guid',
    //             'tally_ledgers.opening_balance',
    //             DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as debit'),
    //             DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as credit'),
    //             DB::raw('(tally_ledgers.opening_balance + 
    //                     SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) + 
    //                     SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END)) as closing_balance')
    //         )
    //         ->whereIn('tally_ledgers.company_guid', $companyGuids)
    //         ->whereIn('tally_vouchers.company_guid', $companyGuids)
    //         ->groupBy('tally_ledgers.language_name', 'tally_ledgers.guid', 'tally_ledgers.opening_balance');

    //     return DataTables::of($query)
    //         ->addIndexColumn()
    //         ->editColumn('opening_balance', function ($row) {
    //             return $this->formatNumber($row->opening_balance);
    //         })
    //         ->editColumn('debit', function ($row) {
    //             return $this->formatNumber($row->debit);
    //         })
    //         ->editColumn('credit', function ($row) {
    //             return $this->formatNumber($row->credit);
    //         })
    //         ->editColumn('closing_balance', function ($row) {
    //             return $this->formatNumber($row->closing_balance);
    //         })
    //         ->make(true);
    // }

}
