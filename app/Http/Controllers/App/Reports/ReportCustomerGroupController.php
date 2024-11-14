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
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $customerGroupQuery = TallyLedgerGroup::select(
                        'tally_ledger_groups.ledger_group_name',
                        'tally_ledger_groups.ledger_group_id',
                        'tally_ledger_groups.parent',
                    )->whereIn('tally_ledger_groups.company_id', $companyIds)
                    ->where('tally_ledger_groups.ledger_group_name', 'Sundry Debtors')
                    ->leftJoin('tally_ledgers', 'tally_ledger_groups.ledger_group_id', '=', 'tally_ledgers.ledger_group_id')
                    ->whereIn('tally_ledgers.company_id', $companyIds)
                    ->leftJoin('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                    ->leftJoin('tally_vouchers', function ($join) {
                        $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                            ->where('tally_vouchers.is_cancelled', 0)
                            ->where('tally_vouchers.is_optional', 0);
                    })
                    ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                    ->selectRaw('SUM(CASE 
                                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                            AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id 
                                        THEN tally_voucher_heads.amount 
                                        ELSE 0 
                                    END) AS total_sales,
                                COUNT(DISTINCT CASE 
                                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                        THEN tally_vouchers.voucher_id 
                                        ELSE NULL 
                                    END) AS transaction,
                                CASE 
                                    WHEN COUNT(DISTINCT CASE 
                                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                        THEN tally_vouchers.voucher_id 
                                        ELSE NULL 
                                    END) > 0 
                                    THEN SUM(CASE 
                                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                            AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id 
                                        THEN tally_voucher_heads.amount 
                                        ELSE 0 
                                    END) / COUNT(DISTINCT CASE 
                                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                        THEN tally_vouchers.voucher_id 
                                        ELSE NULL 
                                    END)
                                    ELSE 0 
                                END AS avg_sales
                    ')
                    ->groupBy('tally_ledger_groups.ledger_group_id');

            Log::info("Customer Group Query");        
            Log::info($this->reportService->getFinalQuery($customerGroupQuery));

            $customerGroup = $customerGroupQuery->get();

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportCustomerGroupController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($customerGroup)
                ->addIndexColumn()
                ->addColumn('total_sales', function ($data) {
                    $totalSales = $data->total_sales;
                    return indian_format(abs($totalSales));
                })
                ->addColumn('transaction', function ($data) {
                    $transaction = $data->transaction;
                    return indian_format(abs($transaction));
                })
                ->addColumn('avg_sales', function ($data) {
                    $avgSales = $data->avg_sales;
                    return indian_format(abs($avgSales));
                })
                ->make(true);

                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                Log::info('Total end execution time for ReportCustomerGroupController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
                return $dataTable;
        }
    }

    public function AllCustomerGroupLedgerReports($customerGroupLedgerId)
    {
        $companyIds = $this->reportService->companyData();

        $customerGroupLedger = TallyLedgerGroup::whereIn('company_id', $companyIds)
                                        ->findOrFail($customerGroupLedgerId);

        $menuItems = TallyLedgerGroup::where('ledger_group_name', $customerGroupLedger->ledger_group_name)->whereIn('company_id', $companyIds)->get();

        return view('app.reports.customerGroup._customer_group_ledger', [
            'customerGroupLedger' => $customerGroupLedger,
            'customerGroupLedgerId' => $customerGroupLedgerId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getCustomerGroupLedgerData($customerGroupLedgerId)
    {
        $companyIds = $this->reportService->companyData();
    
        $generalLedger = TallyLedgerGroup::whereIn('company_id', $companyIds)
                                    ->findOrFail($customerGroupLedgerId);
        $generalLedgerName = $generalLedger->ledger_group_name;
    
        $normalizedNames = [
            'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
            'Direct Incomes, Income (Direct)' => 'Direct Incomes',
            'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
            'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
        ];
    
        if (array_key_exists($generalLedgerName, $normalizedNames)) {
            $generalLedgerName = $normalizedNames[$generalLedgerName];
        }
    
        $overallTotalSales = TallyVoucherHead::select(DB::raw('SUM(tally_voucher_heads.amount) as total_sales'))
            ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
            ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
            ->whereIn('tally_vouchers.company_id', $companyIds)
            ->where('tally_vouchers.is_cancelled', 0)
            ->where('tally_vouchers.is_optional', 0)
            ->where('tally_voucher_types.voucher_type_name', 'Sales')
            ->first()
            ->total_sales;
    
        $overallTotalSales = $overallTotalSales ?? 0;
    
        $query = TallyLedger::select(
                    'tally_ledgers.ledger_name as name',
                    DB::raw('SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" THEN tally_voucher_heads.amount ELSE 0 END) as total_sales'),
                    DB::raw('COUNT(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" THEN tally_voucher_heads.amount END) as transactions_count'),
                    DB::raw('AVG(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" THEN tally_voucher_heads.amount ELSE NULL END) as avg_sales')
                )
                ->leftJoin('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
                ->leftJoin('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                ->whereIn('tally_ledgers.company_id', $companyIds)
                ->where('tally_ledgers.parent', $generalLedgerName)
                ->where('tally_vouchers.is_cancelled', 0)
                ->where('tally_vouchers.is_optional', 0)
                ->where('tally_voucher_types.voucher_type_name', 'Sales')
                ->groupBy('tally_ledgers.ledger_id', 'tally_ledgers.ledger_name')
                ->orderBy('total_sales', '
                DESC')->get();

                dd($query);
    
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_sales', function ($data) {
                    $totalSales = $data->total_sales;
                    return indian_format(abs($totalSales));
            })
            ->addColumn('percentage', function ($row) use ($overallTotalSales) {
                if ($overallTotalSales == 0) {
                    return '0%';
                }
                $percentage = ($row->total_sales / $overallTotalSales) * 100;
                return $this->formatNumber($percentage) . '%';
            })
            ->editColumn('total_count', function ($data) {
                $totalCount = $data->transactions_count;
                return indian_format(abs($totalCount));
            })
            ->editColumn('avg_sales', function ($data) {
                $totalAvgSales = $data->avg_sales;
                return indian_format(abs($totalAvgSales));
            })
            ->rawColumns(['percentage'])
            ->make(true);
    }
    


    // public function getCustomerGroupLedgerData($customerGroupLedgerId)
    // {
    //     $companyIds = $this->reportService->companyData();

    //     $generalLedger = TallyLedgerGroup::whereIn('company_id', $companyIds)
    //                                 ->findOrFail($customerGroupLedgerId);
    //     $generalLedgerName = $generalLedger->ledger_group_name;
    
    //     $normalizedNames = [
    //         'Direct Expenses, Expenses (Direct)' => 'Direct Expenses',
    //         'Direct Incomes, Income (Direct)' => 'Direct Incomes',
    //         'Indirect Expenses, Expenses (Indirect)' => 'Indirect Expenses',
    //         'Indirect Incomes, Income (Indirect)' => 'Indirect Incomes',
    //     ];
    
    //     if (array_key_exists($generalLedgerName, $normalizedNames)) {
    //         $generalLedgerName = $normalizedNames[$generalLedgerName];
    //     }
    
    //     $query = TallyLedger::where('tally_ledgers.parent', $generalLedgerName)
    //         ->leftJoin('tally_voucher_heads', 'tally_ledgers.ledger_id', '=', 'tally_voucher_heads.ledger_id')
    //         ->leftJoin('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
    //         ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
    //         // ->select(
    //         //     'tally_ledgers.ledger_name',
    //         //     'tally_ledgers.ledger_guid',
    //         //     'tally_ledgers.opening_balance',
    //         //     DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as debit'),
    //         //     DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as credit'),
    //         //     DB::raw('(tally_ledgers.opening_balance + 
    //         //             SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) - 
    //         //             SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END)) as closing_balance'),
    //         //     DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) as sales_amount'),
    //         //     DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END) as credit_note_amount'),
    //         //     DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN 1 ELSE 0 END) as sales_count'),
    //         //     DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN 1 ELSE 0 END) as credit_note_count'),
    //         //     DB::raw('SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) - 
    //         //     SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END) as total_sales'),
    //         //     DB::raw('100 * SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) / 
    //         //     NULLIF(SUM(CASE WHEN tally_vouchers.voucher_type = "sales" THEN tally_voucher_heads.amount ELSE 0 END) - 
    //         //     SUM(CASE WHEN tally_vouchers.voucher_type = "credit note" THEN tally_voucher_heads.amount ELSE 0 END), 0) as percentage')
    //         // )
    //         ->whereIn('tally_ledgers.company_id', $companyIds)
    //         ->where('tally_vouchers.is_cancelled', 0)
    //         ->where('tally_vouchers.is_optional', 0)
    //         ->groupBy('tally_ledgers.ledger_name', 'tally_ledgers.ledger_guid', 'tally_ledgers.opening_balance');

    //             return DataTables::of($query)
    //                 ->addIndexColumn()
    //                 // ->editColumn('percentage', function ($row) {
    //                 //         return $this->formatNumber($row->percentage) . '%';
    //                 //     })
    //                 // ->editColumn('opening_balance', function ($row) {
    //                 //     return $this->formatNumber($row->opening_balance);
    //                 // })
    //                 // ->editColumn('total_sales', function ($row) {
    //                 //     $totalSales = $row->sales_amount + $row->credit_note_amount;
    //                 //     return $this->formatNumber($totalSales);
    //                 // })
    //                 // ->editColumn('debit', function ($row) {
    //                 //     return $this->formatNumber($row->debit);
    //                 // })
    //                 // ->editColumn('credit', function ($row) {
    //                 //     return $this->formatNumber($row->credit);
    //                 // })
    //                 // ->editColumn('closing_balance', function ($row) {
    //                 //     return $this->formatNumber($row->closing_balance);
    //                 // })
    //                 // ->editColumn('sales_count', function ($row) {
    //                 //     return $row->sales_count;
    //                 // })
    //                 // ->editColumn('credit_note_count', function ($row) {
    //                 //     return $row->credit_note_count;
    //                 // })
    //                 // ->editColumn('total_count', function ($row) {
    //                 //     return $row->sales_count + $row->credit_note_count;
    //                 // })
    //                 // ->editColumn('avg_sales', function ($row) {
    //                 //     $totalSales = $row->sales_amount + $row->credit_note_amount;
                    
    //                 //     if ($row->sales_count == 0) {
    //                 //         return $this->formatNumber(0);
    //                 //     }
                    
    //                 //     $avgSales = $totalSales / $row->sales_count;
    //                 //     return $this->formatNumber($avgSales);
    //                 // })
    //             ->make(true);
    // }

    protected function formatNumber($value)
    {
        if (!is_numeric($value) || $value == 0) {
            return '-';
        }

        $floatValue = (float) $value;
        return number_format(abs($floatValue), 2, '.', '');
    }

}
