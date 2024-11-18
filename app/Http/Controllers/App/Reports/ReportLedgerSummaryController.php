<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;
use App\Models\TallyLedger;
use Illuminate\Http\Request;

class ReportLedgerSummaryController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.reports.ledgerSummary.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();
    
        if ($request->ajax()) {
            $startTime = microtime(true);

            $transactionsSubquery = DB::table('tally_voucher_heads as tvh')
            ->select(
                'tvh.ledger_id',
                DB::raw('SUM(CASE WHEN tvh.amount < 0 THEN ABS(tvh.amount) ELSE 0 END) AS total_debit'),
                DB::raw('SUM(CASE WHEN tvh.amount > 0 THEN tvh.amount ELSE 0 END) AS total_credit'),
                DB::raw('SUM(tvh.amount) AS net_change')
            )
            ->join('tally_vouchers as tv', 'tvh.voucher_id', '=', 'tv.voucher_id')
            ->where(function($query) {
                $query->where('tv.is_optional', 0)
                      ->orWhereNull('tv.is_optional');
            })
            ->where(function($query) {
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
            ->where(function($query) {
                $query->where('tv.is_optional', 0)
                      ->orWhereNull('tv.is_optional');
            })
            ->where(function($query) {
                $query->where('tv.is_cancelled', 0)
                      ->orWhereNull('tv.is_cancelled');
            })
            ->groupBy('tvh.ledger_id');

        $ledgerSummaryQuery = TallyLedger::select(
                'tally_ledgers.ledger_name',
                'tally_ledgers.ledger_guid',
                DB::raw('IFNULL(tally_ledgers.opening_balance, 0) AS `opening_balance`'),
                DB::raw('IFNULL(tr.total_debit, 0) AS `total_debit`'),
                DB::raw('IFNULL(tr.total_credit, 0) AS `total_credit`'),
                DB::raw('IFNULL(tally_ledgers.opening_balance, 0) + IFNULL(tr.net_change, 0) AS `closing_balance`'),
                'lv.latest_voucher_date AS `Latest Voucher Date`' 
            )
            ->leftJoinSub($transactionsSubquery, 'tr', function ($join) {
                $join->on('tally_ledgers.ledger_id', '=', 'tr.ledger_id');
            })
            ->leftJoinSub($latestVoucherDateSubquery, 'lv', function ($join) {
                $join->on('tally_ledgers.ledger_id', '=', 'lv.ledger_id');
            })
            ->whereIn('tally_ledgers.company_id', $companyIds)
            ->orderBy('tally_ledgers.ledger_name');



            Log::info("Customer Query");        
            Log::info($this->reportService->getFinalQuery($ledgerSummaryQuery));

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
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($ledgerSummary)
                ->addIndexColumn()
                ->addColumn('opening_balance', function ($data) {
                    $opening_balance = $data->opening_balance;
                    return indian_format(abs($opening_balance));
                })
                ->addColumn('total_debit', function ($data) {
                    $totalDebit = $data->total_debit;
                    return indian_format(abs($totalDebit));
                })
                ->addColumn('total_credit', function ($data) {
                    $totalCredit = $data->total_credit;
                    return indian_format(abs($totalCredit));
                })
                ->addColumn('closing_balance', function ($data) {
                    $closing_balance = $data->closing_balance;
                    return indian_format(abs($closing_balance));
                })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }
}
