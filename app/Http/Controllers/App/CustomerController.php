<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyVoucherHead;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class CustomerController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.customers.index');
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
            $ledgerGroupName = 'Sundry Debtors';

            $ledgerGroupName = ($ledgerGroupName && strtolower($ledgerGroupName) !== 'null') ? $ledgerGroupName : 'Sundry Debtors';

            $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
            $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

            // Adjust dates based on custom date range
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

            $companyIdsList = implode(',', $companyIds);

            $sql = "CALL get_ledgers_data(:company_ids, :start_date, :end_date, :ledger_group_name)";

            Log::info("Calling Stored Procedure", [
                'sql' => $sql,
                'params' => [
                    'company_ids' => $companyIdsList,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'ledger_group_name' => $ledgerGroupName,
                ]
            ]);

            try {
                $customers = DB::select($sql, [
                    'company_ids' => $companyIdsList,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'ledger_group_name' => $ledgerGroupName,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_ledgers_data:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(($outstanding));
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }


    public function otherLedgers()
    {
        return View('app.customers.ledger');
    }

    public function ledgergetData(Request $request)
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

            $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
            $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;
    

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
                            lg.ledger_group_name NOT IN ('Sundry Creditors', 'Sundry Debtors')
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

            Log::info("Other Customer Query", ['sql' => $sql]);

            $customers = DB::select(DB::raw($sql));

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return indian_format(($outstanding));
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }


    // public function ledgergetData(Request $request)
    // {
    //     $companyIds = $this->reportService->companyData();

    //     if (empty($companyIds)) {
    //         return DataTables::of([])->make(true);
    //     }

    //     if ($request->ajax()) {
    //         $startTime = microtime(true);

    //         $startDate = $request->get('start_date');
    //         $endDate = $request->get('end_date');
    //         $customDateRange = $request->get('custom_date_range');
    //         // $ledgerGroupName = $request->get('ledger_group_name');
    //         $ledgerGroupName =  ['Sundry Creditors', 'Sundry Debtors'];
          
    //         // dd($ledgerGroupName);
    //         $ledgerGroupName = ($ledgerGroupName && strtolower($ledgerGroupName) !== 'null') ? $ledgerGroupName : 'Sundry Debtors';

    //         $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
    //         $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

    //         if ($customDateRange) {
    //             switch ($customDateRange) {
    //                 case 'this_month':
    //                     $startDate = now()->startOfMonth()->toDateString();
    //                     $endDate = now()->endOfMonth()->toDateString();
    //                     break;
    //                 case 'last_month':
    //                     $startDate = now()->subMonth()->startOfMonth()->toDateString();
    //                     $endDate = now()->subMonth()->endOfMonth()->toDateString();
    //                     break;
    //                 case 'this_quarter':
    //                     $startDate = now()->firstOfQuarter()->toDateString();
    //                     $endDate = now()->lastOfQuarter()->toDateString();
    //                     break;
    //                 case 'prev_quarter':
    //                     $startDate = now()->subQuarter()->firstOfQuarter()->toDateString();
    //                     $endDate = now()->subQuarter()->lastOfQuarter()->toDateString();
    //                     break;
    //                 case 'this_year':
    //                     $startDate = now()->startOfYear()->toDateString();
    //                     $endDate = now()->endOfYear()->toDateString();
    //                     break;
    //                 case 'prev_year':
    //                     $startDate = now()->subYear()->startOfYear()->toDateString();
    //                     $endDate = now()->subYear()->endOfYear()->toDateString();
    //                     break;
    //                 case 'all':
    //                     break;
    //             }
    //         }

    //         $companyIdsList = implode(',', $companyIds);

    //         $sql = "CALL get_ledgers_data(:company_ids, :start_date, :end_date, :ledger_group_name)";

    //         Log::info("Calling Stored Procedure", [
    //             'sql' => $sql,
    //             'params' => [
    //                 'company_ids' => $companyIdsList,
    //                 'start_date' => $startDate,
    //                 'end_date' => $endDate,
    //                 'ledger_group_name' => $ledgerGroupName,
    //             ]
    //         ]);

    //         try {
    //             $customers = DB::select($sql, [
    //                 'company_ids' => $companyIdsList,
    //                 'start_date' => $startDate,
    //                 'end_date' => $endDate,
    //                 'ledger_group_name' => $ledgerGroupName,
    //             ]);
    //         } catch (\Exception $e) {
    //             Log::error('Error executing stored procedure get_ledgers_data:', ['error' => $e->getMessage()]);
    //             return response()->json(['error' => 'Failed to retrieve data.'], 500);
    //         }

    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime1 . ' seconds']);

    //         $dataTable = DataTables::of($customers)
    //             ->addIndexColumn()
    //             ->addColumn('outstanding', function ($data) {
    //                 $outstanding = $data->outstanding;
    //                 return indian_format(($outstanding));
    //             })
    //             ->make(true);

    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime . ' seconds']);

    //         return $dataTable;
    //     }

    //     return response()->json(['message' => 'Invalid request.'], 400);
    // }

    public function show($customer)
    {
        $companyIds = $this->reportService->companyData();

        $ledger = TallyLedger::where('ledger_guid', $customer)
                                ->whereIn('company_id', $companyIds)
                                ->firstOrFail();

        return view('app.customers._customers-view', compact('ledger'));
    }

    public function getVoucherEntries($customer, Request $request)
    {
        $startTime = microtime(true);
        \DB::enableQueryLog();
        $companyIds = $this->reportService->companyData();

        $ledger = TallyLedger::where('ledger_guid', $customer)
            ->whereIn('company_id', $companyIds)
            ->firstOrFail();

        $ledgerId = $ledger->ledger_id;

        $voucherHeads = TallyVoucherHead::where('tally_voucher_heads.ledger_id', $ledgerId)
                        ->join('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                        ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                        ->join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                        ->where(function($query) {
                            $query->whereNull('tally_vouchers.is_optional')
                                ->orWhere('tally_vouchers.is_optional', false);
                        })
                        ->orderBy('tally_vouchers.voucher_date', 'asc')
                        ->select([
                            'tally_vouchers.voucher_date',
                            'tally_vouchers.voucher_number',
                            'tally_vouchers.voucher_id',
                            'tally_voucher_heads.entry_type',
                            'tally_voucher_heads.amount',
                            'tally_voucher_types.voucher_type_name',
                            DB::raw("(SELECT GROUP_CONCAT(DISTINCT l2.ledger_name SEPARATOR ', ')
                                    FROM tally_voucher_heads vh2
                                    JOIN tally_ledgers l2 ON vh2.ledger_id = l2.ledger_id
                                    JOIN tally_ledger_groups lg2 ON l2.ledger_group_id = lg2.ledger_group_id
                                    WHERE vh2.voucher_id = tally_voucher_heads.voucher_id
                                    AND vh2.ledger_id != tally_voucher_heads.ledger_id
                                    AND lg2.ledger_group_name NOT IN ('Sundry Debtors', 'Sundry Creditors', 'Duties & Taxes')
                                    ) AS counterpart_ledger_name")
                        ])
                        ->get();

        \Log::info('Query Log: ', \DB::getQueryLog());

        $runningBalance = 0;
        $openingBalanceAdded = false;

        $groupedVouchers = $voucherHeads->groupBy('voucher_number')->map(function ($entries) use (&$runningBalance, &$openingBalanceAdded, $ledger) {
            $totalAmount = $entries->sum('amount');

            $openingBalance = floatval($ledger->opening_balance ?? 0);
            if (!$openingBalanceAdded) {
                $runningBalance += $openingBalance;
                $openingBalanceAdded = true;
            }
            $runningBalance += $totalAmount;

            return [
                'voucher_number' => $entries->first()->voucher_number,
                'voucher_id' => $entries->first()->voucher_id,
                'amount' => $totalAmount,
                'running_balance' => ($runningBalance == 0 || empty($runningBalance)) ? '0.00' : indian_format($runningBalance),
                'voucher_date' => $entries->first()->voucher_date,
                'voucher_type_name' => $entries->first()->voucher_type_name,
                'ledger_name' =>$entries->first()->counterpart_ledger_name,
                'entry_type' => $entries->first()->entry_type
            ];
        })->values();

        // Apply date filters
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $customDateRange = $request->get('custom_date_range');

        $startDate = ($startDate && strtolower($startDate) !== 'null') ? $startDate : null;
        $endDate = ($endDate && strtolower($endDate) !== 'null') ? $endDate : null;

        
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
            $groupedVouchers = $groupedVouchers->filter(function ($entry) use ($startDate, $endDate) {
                $voucherDate = \Carbon\Carbon::parse($entry['voucher_date']);
                return $voucherDate->between($startDate, $endDate);
            });
        }


        $totalInvoices = $voucherHeads->count();
        $firstVoucherDate = $groupedVouchers->min('voucher_date');
        $lastVoucherDate = $groupedVouchers->max('voucher_date');

        \Log::info('Combined Entries: ', $groupedVouchers->toArray());

        $dataTableResponse = datatables()->of($groupedVouchers)
            ->addColumn('credit', function ($entry) {
                return $entry['entry_type'] === 'credit' ? indian_format(abs($entry['amount'])) : '0.00';
            })
            ->addColumn('debit', function ($entry) {
                return $entry['entry_type'] === 'debit' ? indian_format(abs($entry['amount'])) : '0.00';
            })
            ->addColumn('running_balance', function ($entry) {
                return $entry['running_balance'] ?? "";
            })
            ->addColumn('voucher_type_name', function ($entry) {
                return $entry['voucher_type_name'];
            })
            ->addColumn('voucher_date', function ($entry) {
                return \Carbon\Carbon::parse($entry['voucher_date'])->format('d-M-Y');
            })
            ->addColumn('ledger_name', function ($entry) {
                return $entry['ledger_name'];
            })
            ->with([
                'first_voucher_date' => $firstVoucherDate,
                'last_voucher_date' => $lastVoucherDate,
                'total_invoices' => $totalInvoices
            ])
            ->toJson();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        \Log::info('Total execution time for CustomerController.getVoucherEntries:', ['time_taken' => $executionTime . ' seconds']);

        return $dataTableResponse;
    }

}
