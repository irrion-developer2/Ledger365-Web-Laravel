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
                            lg.ledger_group_id,
                            lg.ledger_group_name,
                            lg.parent
                        FROM
                            tally_ledger_groups lg
                        WHERE
                            lg.ledger_group_name = 'Sundry Creditors'
                            AND lg.company_id IN ({$companyIdsList})

                        UNION ALL

                        SELECT
                            lg_child.ledger_group_id,
                            lg_child.ledger_group_name,
                            lg_child.parent
                        FROM
                            tally_ledger_groups lg_child
                        INNER JOIN
                            ledger_group_hierarchy lg_parent
                            ON lg_child.parent = lg_parent.ledger_group_name
                            AND lg_child.company_id IN ({$companyIdsList})
                    )
                    SELECT
                        l.ledger_name,
                        l.ledger_guid,
                        c.company_name,
                        l.party_gst_in AS gstin,
                        (
                            IFNULL(l.opening_balance, 0) 
                            + IFNULL(ob.total_transactions_before_start_date, 0)
                        ) AS opening_balance_as_of_start_date,
                        IFNULL(tp.total_transactions_in_period, 0) AS transactions_in_period,
                        (
                            IFNULL(l.opening_balance, 0)
                            + IFNULL(ob.total_transactions_before_start_date, 0)
                            + IFNULL(tp.total_transactions_in_period, 0)
                        ) AS outstanding
                    FROM
                        tally_ledgers l
                    INNER JOIN
                        ledger_group_hierarchy lg_h ON l.ledger_group_id = lg_h.ledger_group_id
                    INNER JOIN
                        tally_companies c ON l.company_id = c.company_id
                    LEFT JOIN (
                        SELECT
                            vh.ledger_id,
                            SUM(vh.amount) AS total_transactions_before_start_date
                        FROM
                            tally_voucher_heads vh
                        INNER JOIN
                            tally_vouchers v ON vh.voucher_id = v.voucher_id
                        WHERE
                            v.company_id IN ({$companyIdsList})
                            AND v.voucher_date < {$startDateFilter}
                            AND (v.is_optional = 0 OR v.is_optional IS NULL)
                            AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                        GROUP BY
                            vh.ledger_id
                    ) ob ON l.ledger_id = ob.ledger_id
                    LEFT JOIN (
                        -- Sum of transactions during the period
                        SELECT
                            vh.ledger_id,
                            SUM(vh.amount) AS total_transactions_in_period
                        FROM
                            tally_voucher_heads vh
                        INNER JOIN
                            tally_vouchers v ON vh.voucher_id = v.voucher_id
                        WHERE
                            v.company_id IN ({$companyIdsList})
                            AND (
                                v.voucher_date BETWEEN {$startDateFilter} AND {$endDateFilter}
                                OR ({$endDateFilter} IS NULL AND {$startDateFilter} IS NULL)
                            )
                            AND (v.is_optional = 0 OR v.is_optional IS NULL)
                            AND (v.is_cancelled = 0 OR v.is_cancelled IS NULL)
                        GROUP BY
                            vh.ledger_id
                    ) tp ON l.ledger_id = tp.ledger_id
                    WHERE
                        l.company_id IN ({$companyIdsList})
                    ORDER BY
                        l.ledger_name;
            ";
    
            Log::info("Customer Query", ['sql' => $sql]);
    
            $customers = DB::select(DB::raw($sql));
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                // ->addColumn('purchase', function ($data) {
                //     $totalSales = $data->total_purchase;
                //     return indian_format(abs($totalSales));
                // })
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(($outstanding));
                })
                // ->addColumn('payment_collection', function ($data) {
                //     $payment_collection = $data->payment_collection;
                //     return indian_format(abs($payment_collection));
                // })
                ->make(true);
    
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
