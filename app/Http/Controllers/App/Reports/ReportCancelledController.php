<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherHead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class ReportCancelledController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.reports.cancelled.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $vouchers = TallyVoucher::select(
                        'tally_vouchers.voucher_id',
                        'tally_vouchers.company_id',
                        'tally_vouchers.is_optional',
                        'tally_vouchers.is_cancelled',
                        'tally_vouchers.voucher_date',
                        'tally_voucher_types.voucher_type_name',
                        'tally_vouchers.voucher_number',
                        'tally_ledgers.ledger_name'
                    )
                    ->leftJoin('tally_voucher_heads', function ($join) {
                        $join->on('tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id');
                    })
                    ->leftJoin('tally_voucher_types', function ($join) {
                        $join->on('tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                            ->on('tally_vouchers.company_id', '=', 'tally_voucher_types.company_id');
                    })
                    ->leftJoin('tally_ledgers', function ($join) {
                        $join->on('tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id');
                    })
                    ->where('tally_vouchers.is_cancelled', 1)
                    ->whereIn('tally_vouchers.company_id', $companyIds)
                    ->selectRaw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as total_credit')
                    ->selectRaw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as total_debit')
                    ->groupBy(
                        'tally_vouchers.voucher_id',
                        'tally_vouchers.company_id',
                        'tally_vouchers.is_optional',
                        'tally_vouchers.is_cancelled',
                        'tally_vouchers.voucher_date',
                        'tally_voucher_types.voucher_type_name',
                        'tally_vouchers.voucher_number',
                        'tally_ledgers.ledger_name'
                    );


            Log::info("vouchers Query");        
            Log::info($this->reportService->getFinalQuery($vouchers));
         
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Initial DB request execution time:', ['time_taken' => $executionTime1 . ' seconds']);

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
                }
            }

            Log::info('Custom Date Range:', ['customDateRange' => $customDateRange]);
            Log::info('Start Date:', ['startDate' => $startDate]);
            Log::info('End Date:', ['endDate' => $endDate]);

            if ($startDate && $endDate) {
                $vouchers->whereBetween('voucher_date', [$startDate, $endDate]);
            }


            $dataTable = DataTables::of($vouchers)
                ->addIndexColumn()
                ->addColumn('credit', function ($data) {
                    return indian_format(abs($data->total_credit));
                })
                ->addColumn('debit', function ($data) {
                    return indian_format(abs($data->total_debit));
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total execution time for getData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }
}
