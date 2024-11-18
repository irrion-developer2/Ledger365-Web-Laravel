<?php

namespace App\Http\Controllers\App\Reports;

use Carbon\Carbon;
use App\Models\TallyCompany;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\TallyLedgerGroup;
use App\Models\TallyVoucherHead;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ReportBalanceSheetController extends Controller
{

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    
    public function index()
    {
        $companyIds = $this->reportService->companyData();

        $company = TallyCompany::where('company_id', $companyIds)->first();

        return view ('app.reports.balanceSheet.index', compact('company'));
    }


    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $desiredParentGroups = ['Capital Account', 'Loans (Liability)', 'Current Liabilities','Fixed Assets', 'Investments', 'Current Assets'];


            $transactionsSubquery = DB::table('tally_voucher_heads as tvh')
            ->select(
                'tvh.ledger_id',
                DB::raw('SUM(CASE WHEN tvh.amount < 0 THEN ABS(tvh.amount) ELSE 0 END) AS total_debit'),
                DB::raw('SUM(CASE WHEN tvh.amount > 0 THEN tvh.amount ELSE 0 END) AS total_credit'),
                DB::raw('SUM(tvh.amount) AS net_change')
            )
            ->join('tally_vouchers as tv', 'tvh.voucher_id', '=', 'tv.voucher_id')
            ->where(function ($query) {
                $query->where('tv.is_optional', 0)
                      ->orWhereNull('tv.is_optional');
            })
            ->where(function ($query) {
                $query->where('tv.is_cancelled', 0)
                      ->orWhereNull('tv.is_cancelled');
            })
            ->groupBy('tvh.ledger_id');

        $latestVoucherDateSubquery = DB::table('tally_voucher_heads as tvh')
            ->select(
                'tvh.ledger_id',
                DB::raw('MAX(tv.voucher_date) AS latest_voucher_date')
            )
            ->join('tally_vouchers as tv', 'tvh.voucher_id', '=', 'tv.voucher_id')
            ->where(function ($query) {
                $query->where('tv.is_optional', 0)
                      ->orWhereNull('tv.is_optional');
            })
            ->where(function ($query) {
                $query->where('tv.is_cancelled', 0)
                      ->orWhereNull('tv.is_cancelled');
            })
            ->groupBy('tvh.ledger_id');

        $ledgerSummaryQuery = DB::table('tally_ledger_groups as tlg')
            ->select(
                DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name) AS parent_group_name'),
                DB::raw('IFNULL(SUM(tl.opening_balance), 0) AS total_opening_balance'),
                DB::raw('IFNULL(SUM(tr.total_debit), 0) AS total_debit'),
                DB::raw('IFNULL(SUM(tr.total_credit), 0) AS total_credit'),
                DB::raw('IFNULL(SUM(tl.opening_balance), 0) + IFNULL(SUM(tr.net_change), 0) AS total_closing_balance'),
                DB::raw('MAX(lv.latest_voucher_date) AS latest_voucher_date')
            )
            ->leftJoin('tally_ledgers as tl', 'tl.ledger_group_id', '=', 'tlg.ledger_group_id')
            ->leftJoinSub($transactionsSubquery, 'tr', function ($join) {
                $join->on('tl.ledger_id', '=', 'tr.ledger_id');
            })
            ->leftJoinSub($latestVoucherDateSubquery, 'lv', function ($join) {
                $join->on('tl.ledger_id', '=', 'lv.ledger_id');
            })
            ->whereIn('tl.company_id', $companyIds)
            ->whereIn(DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name)'), $desiredParentGroups)
            ->groupBy(DB::raw('COALESCE(tlg.parent, tlg.ledger_group_name)'))
            ->orderBy('parent_group_name');

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
                $ledgerSummaryQuery->whereBetween('lv.latest_voucher_date', [$startDate, $endDate]);
            }

            $ledgerSummary = $ledgerSummaryQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportGroupSummaryController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($ledgerSummary)
                ->addIndexColumn()
                ->addColumn('opening_balance', function ($data) {
                    return indian_format($data->total_opening_balance);
                })
                ->addColumn('total_debit', function ($data) {
                    return indian_format($data->total_debit);
                })
                ->addColumn('total_credit', function ($data) {
                    return indian_format($data->total_credit);
                })
                ->addColumn('closing_balance', function ($data) {
                    return indian_format($data->total_closing_balance);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportGroupSummaryController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }

    // public function index()
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     $company = TallyCompany::where('guid', $companyGuids)->first();

    //     return view ('app.reports.balanceSheet.index', compact('company'));
    // }
    
    // public function getData(Request $request)
    // {
    //     $companyGuids = $this->reportService->companyData();

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $accountTypeCases = '
    //             CASE 
    //                 WHEN name LIKE "%liabilities%" THEN "Liability"
    //                 WHEN name LIKE "%liability%" THEN "Liability"
    //                 WHEN name LIKE "%branch / divisions%" THEN "Liability"
    //                 WHEN name LIKE "%suspense a/c%" THEN "Liability"
    //                 WHEN name LIKE "%capital account%" THEN "Liability"

    //                 WHEN name LIKE "%assets%" THEN "Asset"
    //                 WHEN name LIKE "%asset%" THEN "Asset"
    //                 WHEN name LIKE "%investments%" THEN "Asset"

    //                 WHEN name LIKE "%income%" THEN "Revenue"
    //                 WHEN name LIKE "%revenue%" THEN "Revenue"
    //                 WHEN name LIKE "%sales accounts%" THEN "Revenue"

    //                 WHEN name LIKE "%expense%" THEN "Expense"
    //                 WHEN name LIKE "%purchase%" THEN "Expense"

    //                 ELSE "Other" 
    //             END as account_type
    //         ';

    //         $Balancequery = TallyLedgerGroup::where(function($query) {
    //                         $query->where('parent', '')->orWhereNull('parent');
    //                     })
    //                     ->whereIn('company_guid', $companyGuids)
    //                     ->selectRaw("guid, name, parent, company_guid, $accountTypeCases");
    
    //         Log::info("BalanceSheet Query");        
    //         Log::info($this->reportService->getFinalQuery($Balancequery));
    
    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
    //         $dataTable = DataTables::of($Balancequery)
    //             ->addIndexColumn()
    //             ->editColumn('amount', function ($data) use ($companyGuids) {

    //                 $name = $data->name;

    //                 foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
    //                     if (strpos($name, $pattern) !== false) {
    //                         $name = $normalized;
    //                         break;
    //                     }
    //                 }

    //                 $groupLedgerIdsQuery = TallyLedgerGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
    //                 $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');
    
    //                 if ($groupLedgerIds->isNotEmpty()) {
    //                     $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
    //                             ->whereIn('company_guid', $companyGuids)
    //                             ->pluck('guid');
    //                 } else {
    //                     $ledgerIds = TallyLedger::where('parent', $name)
    //                             ->whereIn('company_guid', $companyGuids)
    //                             ->pluck('guid');
    //                 }
    
    //                 $allLedgerIds = $ledgerIds->unique();
    
    //                 if ($allLedgerIds->isEmpty()) {
    //                     return '-';
    //                 }
    
    //                 $totalAmount = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
    //                                                 ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds)
    //                                                 ->whereNot('tally_vouchers.is_cancelled', 'Yes')
    //                                                 ->whereNot('tally_vouchers.is_optional', 'Yes')
    //                                                 ->sum('tally_voucher_heads.amount');

    //                 if ($totalAmount == 0) {
    //                     return '-';
    //                 }
    

    //                 // dd($totalAmount);
    //                 return indian_format(abs($totalAmount));
    //             })
    //             ->filter(function ($query) {
    //                 $query->get()->filter(function ($item) {
    //                     $name = strtolower($item->name);
    //                     return strpos($name, 'liabilities') !== false || strpos($name, 'liability') !== false;
    //                 });
    //             })
    //             ->filter(function ($query) {
    //                 $query->get()->filter(function ($item) {
    //                     $name = strtolower($item->name);
    //                     return strpos($name, 'assets') !== false || strpos($name, 'asset') !== false;
    //                 });
    //             })

    //             ->make(true);
            
    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
    //         return $dataTable;
    //     }
    // }

}
