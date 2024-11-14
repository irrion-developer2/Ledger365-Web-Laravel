<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucher;
use App\Models\TallyLedger;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view('app.sales.index');
    }

    public function getData(Request $request)
    {
        // Step 1: Retrieve Company IDs Relevant for the Report
        $companyIds = $this->reportService->companyData();

        // Step 2: Check if the Request is AJAX
        if ($request->ajax()) {
            $startTime = microtime(true);

            // Step 3: Define the Base Raw SQL Query with Placeholders for Company IDs
            $salesQuery = "
                SELECT 
                    `tally_vouchers`.`voucher_id`, 
                    `tally_vouchers`.`company_id`, 
                    `tally_vouchers`.`voucher_type_id`, 
                    `tally_voucher_types`.`voucher_type_name`, 
                    `tally_ledgers`.`ledger_name`, 
                    `tally_vouchers`.`voucher_date`, 
                    `tally_vouchers`.`voucher_number`, 
                    `tally_vouchers`.`place_of_supply`, 
                    COALESCE(SUM(CASE 
                        WHEN tally_voucher_types.voucher_type_name = 'Sales' 
                             AND tally_voucher_heads.entry_type = 'debit' 
                        THEN tally_voucher_heads.amount 
                        ELSE 0 
                    END), 0) AS total_debit
                FROM 
                    `tally_vouchers` 
                LEFT JOIN 
                    `tally_voucher_heads` 
                    ON `tally_vouchers`.`voucher_id` = `tally_voucher_heads`.`voucher_id` 
                LEFT JOIN 
                    `tally_voucher_types` 
                    ON `tally_vouchers`.`voucher_type_id` = `tally_voucher_types`.`voucher_type_id` 
                    AND `tally_vouchers`.`company_id` = `tally_voucher_types`.`company_id` 
                LEFT JOIN 
                    `tally_ledgers` 
                    ON `tally_voucher_heads`.`ledger_id` = `tally_ledgers`.`ledger_id` 
                WHERE 
                    `tally_voucher_types`.`voucher_type_name` = 'Sales' 
                    AND `tally_vouchers`.`is_cancelled` = 0 
                    AND `tally_vouchers`.`is_optional` = 0 
                    AND `tally_vouchers`.`company_id` IN (" . implode(',', array_fill(0, count($companyIds), '?')) . ")
            ";

            // Step 4: Initialize Bindings Array with Company IDs
            $bindings = $companyIds;

            // Step 5: Handle Date Range Filters
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $customDateRange = $request->get('custom_date_range');

            if ($customDateRange) {
                switch ($customDateRange) {
                    case 'this_month':
                        $startDate = Carbon::now()->startOfMonth()->toDateString();
                        $endDate = Carbon::now()->endOfMonth()->toDateString();
                        break;
                    case 'last_month':
                        $startDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                        $endDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                        break;
                    case 'this_quarter':
                        $startDate = Carbon::now()->firstOfQuarter()->toDateString();
                        $endDate = Carbon::now()->lastOfQuarter()->toDateString();
                        break;
                    case 'prev_quarter':
                        $startDate = Carbon::now()->subQuarter()->firstOfQuarter()->toDateString();
                        $endDate = Carbon::now()->subQuarter()->lastOfQuarter()->toDateString();
                        break;
                    case 'this_year':
                        $startDate = Carbon::now()->startOfYear()->toDateString();
                        $endDate = Carbon::now()->endOfYear()->toDateString();
                        break;
                    case 'prev_year':
                        $startDate = Carbon::now()->subYear()->startOfYear()->toDateString();
                        $endDate = Carbon::now()->subYear()->endOfYear()->toDateString();
                        break;
                    case 'all':
                        // No date filter
                        $startDate = null;
                        $endDate = null;
                        break;
                    default:
                        // Handle unexpected values
                        $startDate = null;
                        $endDate = null;
                        break;
                }
            }

            // Step 6: Append Date Filter to SQL Query if Applicable
            if ($startDate && $endDate) {
                $salesQuery .= " AND `tally_vouchers`.`voucher_date` BETWEEN ? AND ?";
                $bindings[] = $startDate;
                $bindings[] = $endDate;
            }

            // Step 7: Append GROUP BY Clause
            $salesQuery .= "
                GROUP BY 
                    `tally_vouchers`.`voucher_id`, 
                    `tally_vouchers`.`company_id`, 
                    `tally_vouchers`.`voucher_type_id`, 
                    `tally_voucher_types`.`voucher_type_name`, 
                    `tally_ledgers`.`ledger_name`, 
                    `tally_vouchers`.`voucher_date`, 
                    `tally_vouchers`.`voucher_number`, 
                    `tally_vouchers`.`place_of_supply`
            ";

            // Step 8: Log the Final SQL Query and Bindings for Debugging
            Log::info("Sales Query:", [
                'sql' => $salesQuery,
                'bindings' => $bindings
            ]);

            // Step 9: Execute the Raw SQL Query with Bindings
            $sales = DB::select($salesQuery, $bindings);

            // Step 10: Convert the Result to a Laravel Collection for DataTables
            $salesCollection = collect($sales);

            // Step 11: Log Date Range Information
            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            // Step 12: Log Execution Time for the First DB Request
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first DB request execution time for SalesController.getDATA:', [
                'time_taken' => $executionTime1 . ' seconds'
            ]);

            // Step 13: Ensure the `indian_format` Function Exists
            if (!function_exists('indian_format')) {
                function indian_format($number) {
                    return number_format((float)$number, 2, '.', ',');
                }
            }

            // Step 14: Integrate with DataTables
            $dataTable = DataTables::of($salesCollection)
                ->addIndexColumn()
                ->addColumn('debit', function ($data) {
                    $totalDebit = $data->total_debit;
                    return indian_format(abs($totalDebit));
                })
                ->addColumn('voucher_date_formatted', function ($entry) {
                    return Carbon::parse($entry->voucher_date)->format('d-M-Y');
                })
                ->make(true);

            // Step 15: Log Total Execution Time
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for SalesController.getDATA:', [
                'time_taken' => $executionTime . ' seconds'
            ]);

            // Step 16: Return the DataTable Response
            return $dataTable;
        }
    }
    
    
    // public function getData(Request $request)
    // {
    //     $companyIds = $this->reportService->companyData();
    
    //     if ($request->ajax()) {
    //         $startTime = microtime(true);
      
    //         $salesQuery = TallyVoucher::select(
    //                     'tally_vouchers.voucher_id',
    //                     'tally_vouchers.company_id',
    //                     'tally_vouchers.voucher_type_id',
    //                     'tally_voucher_types.voucher_type_name',
    //                     'tally_ledgers.ledger_name',
    //                     'tally_vouchers.voucher_date',
    //                     'tally_vouchers.voucher_number',
    //                     'tally_vouchers.place_of_supply'
    //                 )
    //                 ->where('tally_voucher_types.voucher_type_name', 'Sales')
    //                 ->where('tally_vouchers.is_cancelled', 'No')
    //                 ->where('tally_vouchers.is_optional', 'No')
    //                 ->whereIn('tally_vouchers.company_id', $companyIds)
    //                 ->leftJoin('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
    //                 ->leftJoin('tally_voucher_types', function($join) {
    //                     $join->on('tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
    //                         ->on('tally_vouchers.company_id', '=', 'tally_voucher_types.company_id');
    //                 })
    //                 ->leftJoin('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
    //                 ->selectRaw('
    //                     COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" AND tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END), 0) as total_debit
    //                 ')
    //                 ->groupBy(
    //                     'tally_vouchers.voucher_id',
    //                     'tally_vouchers.company_id',
    //                     'tally_vouchers.voucher_type_id',
    //                     'tally_voucher_types.voucher_type_name',
    //                     'tally_ledgers.ledger_name',
    //                     'tally_vouchers.voucher_date',
    //                     'tally_vouchers.voucher_number',
    //                     'tally_vouchers.place_of_supply'
    //                 );

    //         $salesQuery = 

            
    //         Log::info("Sales Query");        
    //         Log::info($this->reportService->getFinalQuery($salesQuery));

    //         $startDate = $request->get('start_date');
    //         $endDate = $request->get('end_date');
    //         $customDateRange = $request->get('custom_date_range');
    
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
    //         if ($startDate && $endDate) {
    //             $salesQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
    //         }
    
    //         $sales = $salesQuery->get();

    //         Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
    //         Log::info('Start date:', ['startDate' => $startDate]);
    //         Log::info('End date:', ['endDate' => $endDate]);
    
    //         $endTime1 = microtime(true);
    //         $executionTime1 = $endTime1 - $startTime;
    //         Log::info('Total first db request execution time for SalesController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
    //         $dataTable = DataTables::of($sales)
    //             ->addIndexColumn()
    //             ->addColumn('debit', function ($data) {
    //                 $totalDebit = $data->total_debit;
    //                 return indian_format(abs($totalDebit));
    //             })
    //             ->addColumn('voucher_date', function ($entry) {
    //                 return \Carbon\Carbon::parse($entry->voucher_date)->format('d-M-Y');
    //             })
    //             ->make(true);
    
    //         $endTime = microtime(true);
    //         $executionTime = $endTime - $startTime;
    //         Log::info('Total end execution time for SalesController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
    //         return $dataTable;
    //     }
    // }
    
    public function AllSaleItemReports($saleItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleItemId);

        $saleItemName = TallyVoucher::where('party_ledger_name', $saleItem->party_ledger_name)
                                    ->whereNot('is_cancelled', 'Yes')
                                    ->whereNot('is_optional', 'Yes')
                                    ->whereIn('company_guid', $companyGuids)->get();
        $saleReceiptItem = $saleItemName->firstWhere('voucher_type', 'Receipt');

         if ($saleReceiptItem) {
            $voucherHeadsSaleReceipt = TallyVoucherHead::where('tally_voucher_id', $saleReceiptItem->id)
                ->where('entry_type', 'credit')
                ->get();
        } else {
            $voucherHeadsSaleReceipt = collect();
        }


        $ledgerData = TallyLedger::where('name', $saleItem->party_ledger_name)->whereIn('company_guid', $companyGuids)->get();
        if ($ledgerData instanceof \Illuminate\Support\Collection) {
            $ledgerItem = $ledgerData->first();
        } else {
            $ledgerItem = $ledgerData;
        }

        $creditPeriod = intval($ledgerItem->bill_credit_period ?? 0);
        $voucherDate = \Carbon\Carbon::parse($saleItem->voucher_date);
        $dueDate = $voucherDate->copy()->addDays($creditPeriod);


        $voucherHeadsName = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->get();
            $successfulAllocations = [];
            foreach ($voucherHeadsName as $voucherHead) {
                $id = $voucherHead->id;

                $bankAllocations = TallyBankAllocation::where('head_id', $id)->get();
                if ($bankAllocations->isNotEmpty()) {
                    $successfulAllocations[] = [
                        'voucher_head' => $voucherHead,
                        'bank_allocations' => $bankAllocations,
                    ];
                }
            }
        $pendingVoucherHeads = TallyVoucherHead::where('ledger_name', $saleItem->party_ledger_name)->get();

        $voucherHeads = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->get();

        $gstVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($saleItem) {
            return $voucherHead->ledger_name !== $saleItem->party_ledger_name;
        });


        $voucherItems = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->get();
        $uniqueGstLedgerSources = $voucherItems->pluck('gst_ledger_source')->unique();
        $totalCountItems = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->count();
        $totalCountLinkHeads = $voucherHeadsSaleReceipt->count();
        $totalCountHeads = TallyVoucherHead::where('tally_voucher_id', $saleItemId)->count();
        $subtotalsamount = $voucherItems->sum('amount');

        $menuItems = TallyVoucher::where('voucher_type', 'Sales')
                                    ->whereNot('is_cancelled', 'Yes')
                                    ->whereNot('is_optional', 'Yes')
                                    ->whereIn('company_guid', $companyGuids)
                                    ->get();

        return view('app.sales._sale_item_list', [
            'saleItem' => $saleItem,
            'ledgerData' => $ledgerData,
            'voucherHeads' => $voucherHeads,
            'gstVoucherHeads' => $gstVoucherHeads,
            'totalCountItems' => $totalCountItems,
            'uniqueGstLedgerSources' => $uniqueGstLedgerSources,
            'subtotalsamount' => $subtotalsamount,
            'saleReceiptItem' => $saleReceiptItem,
            'voucherHeadsSaleReceipt' => $voucherHeadsSaleReceipt,
            'dueDate' => $dueDate,
            'saleItemId' => $saleItemId ,
            'menuItems' => $menuItems,
            'pendingVoucherHeads' => $pendingVoucherHeads,
            'totalCountLinkHeads' => $totalCountLinkHeads,
            'totalCountHeads' => $totalCountHeads
        ]);
    }

    public function getSaleItemData($saleItemId)
    {
        $companyGuids = $this->reportService->companyData();

        $saleItem = TallyVoucher::whereIn('company_guid', $companyGuids)
                                    ->findOrFail($saleItemId);
        $saleItemName = $saleItem->party_ledger_name;

        $query = TallyVoucherItem::where('tally_voucher_id', $saleItemId)->get();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($request) {
                return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }

}
