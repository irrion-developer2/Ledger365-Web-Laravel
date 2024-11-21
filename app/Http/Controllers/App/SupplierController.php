<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return View('app.suppliers.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();
    
        if (empty($companyIds)) {
            return DataTables::of([])->make(true);
        }

        if ($request->ajax()) {
            $startTime = microtime(true);
    
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
    
            $startDateFilter = $startDate ? "'{$startDate}'" : 'NULL';
            $endDateFilter = $endDate ? "'{$endDate}'" : 'NULL';
    
            $companyIdsList = implode(',', $companyIds);
    
            $sql = "
                WITH RECURSIVE ledger_group_hierarchy AS (
                    SELECT
                        ledger_group_id,
                        ledger_group_name,
                        parent,
                        company_id
                    FROM
                        tally_ledger_groups
                    WHERE
                        ledger_group_name = 'Sundry Creditors'
    
                    UNION ALL
    
                    SELECT
                        lg.ledger_group_id,
                        lg.ledger_group_name,
                        lg.parent,
                        lg.company_id
                    FROM
                        tally_ledger_groups lg
                    INNER JOIN
                        ledger_group_hierarchy lgh
                        ON lg.parent = lgh.ledger_group_name
                        AND lg.company_id = lgh.company_id
                )
                SELECT
                    tl.company_id,
                    c.company_name, 
                    tl.ledger_guid,
                    tl.ledger_name,
                    tl.party_gst_in,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN tvt.voucher_type_name = 'Purchase'
                                THEN tvh.amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_purchase,
                    COALESCE(SUM(tvh.amount), 0) AS outstanding,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN tvt.voucher_type_name = 'Payment'
                                THEN tvh.amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS payment_collection
                FROM
                    tally_ledgers tl
                INNER JOIN
                    ledger_group_hierarchy lgh
                    ON tl.ledger_group_id = lgh.ledger_group_id
                LEFT JOIN
                    tally_voucher_heads tvh
                    ON tl.ledger_id = tvh.ledger_id
                LEFT JOIN
                    tally_vouchers tv
                    ON tvh.voucher_id = tv.voucher_id
                    AND (tv.is_cancelled = 0 OR tv.is_cancelled IS NULL)
                    AND (tv.is_optional = 0 OR tv.is_optional IS NULL)
                LEFT JOIN
                    tally_voucher_types tvt
                    ON tv.voucher_type_id = tvt.voucher_type_id
                LEFT JOIN
                    tally_companies c
                    ON tl.company_id = c.company_id 
                WHERE
                    tl.company_id IN ({$companyIdsList})
                    AND ({$startDateFilter} IS NULL OR tv.voucher_date >= {$startDateFilter})
                    AND ({$endDateFilter} IS NULL OR tv.voucher_date <= {$endDateFilter})
                GROUP BY
                    tl.ledger_id;
            ";
    
            Log::info("Customer Query", ['sql' => $sql]);
    
            $customers = DB::select(DB::raw($sql));
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('purchase', function ($data) {
                    $totalSales = $data->total_purchase;
                    return indian_format(abs($totalSales));
                })
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(($outstanding));
                })
                ->addColumn('payment_collection', function ($data) {
                    $payment_collection = $data->payment_collection;
                    return indian_format(abs($payment_collection));
                })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
