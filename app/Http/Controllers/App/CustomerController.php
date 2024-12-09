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

        // $collationVariables = DB::select("SHOW VARIABLES LIKE 'collation%'");
        // Log::info('Collation Variables:', ['variables' => $collationVariables]);

        // $version = DB::select("SELECT VERSION() AS version");
        // Log::info('Database Version:', ['version' => $version]);

        // $currentDatabase = DB::select("SELECT DATABASE() AS database_name");
        // Log::info('Current Database:', ['database_name' => $currentDatabase]);


        if ($request->ajax()) {
            $startTime = microtime(true);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');
            $ledgerGroupName = 'Sundry Debtors';

            $ledgerGroupName = ($ledgerGroupName && strtolower($ledgerGroupName) !== 'null') ? $ledgerGroupName : 'Sundry Debtors';

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
                    $companyIdsList,
                    $startDate,
                    $endDate,
                    $ledgerGroupName,
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
                    return ($outstanding);
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
            $excludedGroupNames = 'Sundry Creditors,Sundry Debtors';
            $excludedGroupNames = (!empty($excludedGroupNames)) ? $excludedGroupNames : NULL;


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

            $companyIdsList = implode(',', $companyIds);

            $sql = "CALL get_other_ledger_data(?, ?, ?, ?)";

            Log::info("Calling Stored Procedure", [
                'sql' => $sql,
                'params' => [
                    'company_ids' => $companyIdsList,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'ledger_group_name' => $excludedGroupNames,
                ]
            ]);

            try {
                $customers = DB::select($sql, [
                    $companyIdsList,         
                    $startDate,          
                    $endDate,  
                    $excludedGroupNames,
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_other_ledger_data:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }


            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('outstanding', function ($data) {
                    $outstanding = $data->outstanding;
                    return ($outstanding);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }


    public function show($customer)
    {
        $companyIds = $this->reportService->companyData();

        $ledger = TallyLedger::where('ledger_guid', $customer)
                                ->whereIn('company_id', $companyIds)
                                ->firstOrFail();

        return view('app.customers._custom-view', compact('ledger'));
    }

    public function getVoucherItemEntries($customer, Request $request)
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
                            DB::raw("(SELECT l2.ledger_name
                                        FROM tally_voucher_heads vh2
                                        JOIN tally_ledgers l2 ON vh2.ledger_id = l2.ledger_id
                                        JOIN tally_ledger_groups lg2 ON l2.ledger_group_id = lg2.ledger_group_id
                                        WHERE vh2.voucher_id = tally_voucher_heads.voucher_id
                                        AND vh2.ledger_id != tally_voucher_heads.ledger_id
                                        AND lg2.ledger_group_name NOT IN ('Sundry Debtors', 'Sundry Creditors', 'Duties & Taxes')
                                        ORDER BY l2.ledger_name ASC
                                        LIMIT 1
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


    public function getVoucherEntries($customer, Request $request)
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
            $ledger = TallyLedger::where('ledger_guid', $customer)
                    ->whereIn('company_id', $companyIds)
                    ->firstOrFail();
        
            $ledgerId = $ledger->ledger_id;

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

            $companyIdsList = implode(',', $companyIds);

            $sql = "CALL get_ledgerView_data(?, ?, ?, ?)";

            Log::info("Calling Stored Procedure", [
                'sql' => $sql,
                'params' => [
                    'p_ledgerId' => $ledgerId,
                    'p_company_ids' => $companyIdsList,
                    'p_start_date' => $startDate,
                    'p_end_date' => $endDate,
                ]
            ]);

            try {
                $customers = DB::select($sql, [
                    $ledgerId,
                    $companyIdsList,         
                    $startDate,          
                    $endDate,  
                ]);
            } catch (\Exception $e) {
                Log::error('Error executing stored procedure get_ledgerView_data:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to retrieve data.'], 500);
            }


            $customersCollection = collect($customers);

            $totalInvoices = $customersCollection->count();
            $firstVoucherDate = $customersCollection->min('voucher_date');
            $lastVoucherDate = $customersCollection->max('voucher_date');

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('credit', function ($entry) {
                    return indian_format(abs($entry->credit_amount));
                })
                ->addColumn('debit', function ($entry) {
                    return indian_format(abs($entry->debit_amount));
                })
                ->addColumn('running_balance', function ($entry) {
                    return $entry->running_balance !== null ? indian_format($entry->running_balance) : "0.00";
                })
                ->addColumn('voucher_type_name', function ($entry) {
                    return $entry->voucher_type_name;
                })
                ->addColumn('voucher_date', function ($entry) {
                    return \Carbon\Carbon::parse($entry->voucher_date)->format('d-M-Y');
                })
                ->addColumn('ledger_name', function ($entry) {
                    return $entry->counterpart_ledger_name;
                })
                ->with([
                    'first_voucher_date' => $firstVoucherDate,
                    'last_voucher_date' => $lastVoucherDate,
                    'total_invoices' => $totalInvoices
                ])
                            // ->toJson();
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for CustomerController.ledgergetData:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }

}
