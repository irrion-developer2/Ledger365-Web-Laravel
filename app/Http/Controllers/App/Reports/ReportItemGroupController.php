<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
use App\Models\TallyItemGroup;
use App\Models\TallyCompany;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class ReportItemGroupController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return View('app.reports.itemGroup.index1');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $itemsQuery = TallyItemGroup::leftJoin('tally_items', 'tally_item_groups.item_group_id', '=', 'tally_items.item_group_id')
                        ->leftJoin('tally_voucher_items', 'tally_items.item_id', '=', 'tally_voucher_items.item_id')
                        ->leftJoin('tally_voucher_heads', 'tally_voucher_items.voucher_head_id', '=', 'tally_voucher_heads.voucher_head_id')
                        ->leftJoin('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                        ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                        ->leftJoin('tally_ledgers', function($join) {
                            $join->on('tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                                ->where('tally_voucher_heads.is_party_ledger', '=', 1);
                        })
                        ->where('tally_vouchers.is_cancelled', 0)
                        ->where('tally_vouchers.is_optional', 0)
                        ->whereIn('tally_item_groups.company_id', $companyIds)
                        ->selectRaw('
                            tally_item_groups.item_group_name,
                            tally_item_groups.item_group_id,
                            SUM(CASE 
                                WHEN tally_voucher_types.voucher_type_name = "sales" THEN tally_voucher_items.amount 
                                WHEN tally_voucher_types.voucher_type_name = "credit note" THEN tally_voucher_items.amount 
                                ELSE 0 
                            END) AS total_sales,
                            SUM(CASE 
                                WHEN tally_voucher_types.voucher_type_name = "sales" THEN tally_voucher_items.billed_qty 
                                WHEN tally_voucher_types.voucher_type_name = "credit note" THEN tally_voucher_items.billed_qty 
                                ELSE 0 
                            END) AS qty_sold,
                            COUNT(DISTINCT CASE 
                                WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                                THEN tally_ledgers.ledger_id 
                                ELSE NULL 
                            END) AS customer_count

                        ')
                        ->groupBy('tally_item_groups.item_group_name','tally_item_groups.item_group_id') 
                        ->orderBy('tally_item_groups.item_group_name'); 

            Log::info("Customer Query");        
            Log::info($this->reportService->getFinalQuery($itemsQuery));

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
                $itemsQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }

            $items = $itemsQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportItemGroupController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($items)
                ->addIndexColumn()
                ->addColumn('total_sales', function ($data) {
                    return indian_format($data->total_sales);
                })
                ->addColumn('avg_sales', function ($data) {
                    return indian_format($data->avg_sales);
                })
                ->addColumn('qty_sold', function ($data) {
                    return indian_format($data->qty_sold);
                })
                ->addColumn('customer_count', function ($data) {
                    return indian_format($data->customer_count);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportItemGroupController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }
    
    public function AllItemGroupLedgerReports($itemGroupLedgerId)
    {
        $companyIds = $this->reportService->companyData();

        $itemGroupLedger = TallyItemGroup::whereIn('company_id', $companyIds)->findOrFail($itemGroupLedgerId);

        $menuItems = TallyItemGroup::select('item_group_id', 'item_group_name')
                    ->whereIn('company_id', $companyIds)
                    ->get();


        return view('app.reports.itemGroup._item_group_ledger', [
            'itemGroupLedger' => $itemGroupLedger,
            'itemGroupLedgerId' => $itemGroupLedgerId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getItemGroupLedgerData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $itemsQuery = TallyItem::leftJoin('tally_voucher_items', 'tally_items.item_id', '=', 'tally_voucher_items.item_id')
                ->leftJoin('tally_voucher_heads', 'tally_voucher_items.voucher_head_id', '=', 'tally_voucher_heads.voucher_head_id')
                ->leftJoin('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                ->leftJoin('tally_ledgers', function($join) {
                    $join->on('tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                        ->where('tally_voucher_heads.is_party_ledger', '=', 1);
                })
                ->where('tally_vouchers.is_cancelled', 0)
                ->where('tally_vouchers.is_optional', 0)
                ->whereIn('tally_items.company_id', $companyIds)
                ->selectRaw('
                    tally_items.item_id, 
                    tally_items.item_name, 
                    tally_items.parent, 
                    tally_items.item_guid, 
                    SUM(CASE 
                            WHEN tally_voucher_types.voucher_type_name = "sales" THEN tally_voucher_items.amount 
                            WHEN tally_voucher_types.voucher_type_name = "credit note" THEN tally_voucher_items.amount 
                            ELSE 0 
                        END) AS total_sales,
                    SUM(CASE 
                            WHEN tally_voucher_types.voucher_type_name = "sales" THEN tally_voucher_items.billed_qty 
                            WHEN tally_voucher_types.voucher_type_name = "credit note" THEN tally_voucher_items.billed_qty 
                            ELSE 0 
                        END) AS qty_sold, 
                    COUNT(DISTINCT CASE 
                        WHEN tally_voucher_types.voucher_type_name IN ("sales", "credit note") 
                        THEN tally_ledgers.ledger_id 
                        ELSE NULL 
                    END) AS customer_count,
                    AVG(CASE 
                        WHEN tally_voucher_types.voucher_type_name = "sales" THEN tally_voucher_items.rate 
                        WHEN tally_voucher_types.voucher_type_name = "credit note" THEN tally_voucher_items.rate 
                        ELSE NULL 
                    END) AS avg_sales
                ')
                ->groupBy('tally_items.item_id', 'tally_items.item_name', 'tally_items.parent');

            
            Log::info("Customer Query");        
            Log::info($this->reportService->getFinalQuery($itemsQuery));

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
                $itemsQuery->whereBetween('tally_vouchers.voucher_date', [$startDate, $endDate]);
            }

            $items = $itemsQuery->get();

            Log::info('customDateRange:', ['customDateRange' => $customDateRange]);
            Log::info('Start date:', ['startDate' => $startDate]);
            Log::info('End date:', ['endDate' => $endDate]);

            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportItemGroupController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);

            $dataTable = DataTables::of($items)
                ->addIndexColumn()
                ->addColumn('total_sales', function ($data) {
                    return indian_format($data->total_sales);
                })
                ->addColumn('avg_sales', function ($data) {
                    return indian_format($data->avg_sales);
                })
                ->addColumn('qty_sold', function ($data) {
                    return indian_format($data->qty_sold);
                })
                ->addColumn('customer_count', function ($data) {
                    return indian_format($data->customer_count);
                })
                ->make(true);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportItemGroupController.getDATA:', ['time_taken' => $executionTime . ' seconds']);

            return $dataTable;
        }
    }


    public function AllItemLedgerReports($itemLedgerId)
    {
        $companyIds = $this->reportService->companyData();

        $itemLedger = TallyItem::select('item_id', 'item_name')->whereIn('company_id', $companyIds)
                                ->findOrFail($itemLedgerId);
    
        $menuItems = TallyItem::select('item_id', 'item_name')
                    ->whereIn('company_id', $companyIds)
                    ->get();


        return view('app.reports.itemGroup._item_ledger', [
            'itemLedger' => $itemLedger,
            'itemLedgerId' => $itemLedgerId ,
            'menuItems' => $menuItems
        ]);
    }

    public function getItemLedgerData($itemLedgerId)
    {
        $companyIds = $this->reportService->companyData();

        $itemsQuery = TallyItem::leftJoin('tally_voucher_items', 'tally_items.item_id', '=', 'tally_voucher_items.item_id')
                        ->leftJoin('tally_voucher_heads', 'tally_voucher_items.voucher_head_id', '=', 'tally_voucher_heads.voucher_head_id')
                        ->leftJoin('tally_vouchers', 'tally_voucher_heads.voucher_id', '=', 'tally_vouchers.voucher_id')
                        ->leftJoin('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                        ->where('tally_vouchers.is_cancelled', 0)
                        ->where('tally_vouchers.is_optional', 0)
                        ->whereIn('tally_items.company_id', $companyIds)
                        ->where('tally_voucher_items.item_id', $itemLedgerId)
                        ->select([
                            'tally_vouchers.voucher_id',
                            'tally_vouchers.voucher_date',
                            'tally_voucher_types.voucher_type_name',
                            'tally_vouchers.voucher_number',
                        ])
                        ->selectRaw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) AS debit')
                        ->selectRaw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) AS credit')
                        ->groupBy(['tally_vouchers.voucher_number', 'tally_vouchers.voucher_date', 'tally_voucher_types.voucher_type_name'])
                        ->groupBy([
                            'tally_vouchers.voucher_id',
                            'tally_vouchers.voucher_number',
                            'tally_vouchers.voucher_date',
                            'tally_voucher_types.voucher_type_name',
                        ])
                        ->orderBy('tally_vouchers.voucher_date')->get();

        return DataTables::of($itemsQuery)
            ->addIndexColumn()
            ->editColumn('voucher_date', function ($data) {
                return \Carbon\Carbon::parse($data['voucher_date'])->format('d-M-Y');
            })
            ->editColumn('debit', function ($data) {
                return indian_format($data->debit);
            })
            ->editColumn('credit', function ($data) {
                return indian_format($data->credit);
            })
            ->make(true);
    }

}