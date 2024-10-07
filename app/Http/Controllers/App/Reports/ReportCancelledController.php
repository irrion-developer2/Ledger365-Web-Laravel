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
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $vouchers = TallyVoucher::where('tally_vouchers.is_cancelled', 'Yes')
                                    ->whereIn('company_guid', $companyGuids);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;

            Log::info('Total first db request execution time for ReportCancelledController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

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

            if ($startDate && $endDate) {
                    $vouchers->whereBetween('voucher_date', [$startDate, $endDate]);
            }

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);
            
            $dataTable = DataTables::of($vouchers)
                ->addIndexColumn()
                ->addColumn('credit', function ($data) {
                    $totalCredit = TallyVoucherHead::where('ledger_name', $data->party_ledger_name)
                        ->where('tally_voucher_id', $data->id)
                        ->where('entry_type', 'credit')
                        ->sum('amount');
                
                    return number_format(abs($totalCredit), 2);
                })
                ->addColumn('debit', function ($data) {
                    $totalCredit = TallyVoucherHead::where('ledger_name', $data->party_ledger_name)
                        ->where('tally_voucher_id', $data->id)
                        ->where('entry_type', 'debit')
                        ->sum('amount');
                
                    return number_format(abs($totalCredit), 2);
                })
                ->make(true);

                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                Log::info('Total end execution time for ReportCancelledController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

                return $dataTable;
        }
    }

}
