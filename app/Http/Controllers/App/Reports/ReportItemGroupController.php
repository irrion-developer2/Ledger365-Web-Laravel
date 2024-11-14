<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use App\Models\TallyLedger;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
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
        return View('app.reports.itemGroup.index');
    }

    public function getData(Request $request)
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

        $itemGroupLedger = TallyItem::whereIn('company_id', $companyIds)->findOrFail($itemGroupLedgerId);

        $menuItems = TallyItem::select('tally_items.item_id', 'tally_items.item_name')
                    ->whereIn('tally_items.company_id', $companyIds)
                    ->get();


        return view('app.reports.itemGroup._item_group_ledger', [
            'itemGroupLedger' => $itemGroupLedger,
            'itemGroupLedgerId' => $itemGroupLedgerId ,
            'menuItems' => $menuItems
        ]);
    }

    public function ledgergetData($itemGroupLedgerId)
    {
        $companyIds = $this->reportService->companyData();
        
        $itemGroupLedger = TallyItem::whereIn('company_id', $companyIds)
                                    ->findOrFail($itemGroupLedgerId);
                                    
        $itemGroupLedgerName = $itemGroupLedger->name;

        dd($itemGroupLedgerId);

        $query = TallyLedger::whereHas('tallyVouchers', function ($query) use ($itemGroupLedgerName) {
                $query->whereIn('voucher_type', ['Sales', 'Credit Note'])
                    ->whereHas('tallyVoucherItems', function ($subQuery) use ($itemGroupLedgerName) {
                        $subQuery->where('stock_item_name', $itemGroupLedgerName);
                    });
            })
            ->whereExists(function ($subQuery) use ($itemGroupLedgerName) {
                $subQuery->select(DB::raw(1))
                    ->from('tally_vouchers')
                    ->whereColumn('tally_vouchers.party_ledger_name', 'tally_ledgers.name')
                    ->whereIn('tally_vouchers.voucher_type', ['Sales', 'Credit Note'])
                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                    // ->whereIn('tally_vouchers.company_guid', $companyGuids)
                    ->whereExists(function ($innerQuery) use ($itemGroupLedgerName) {
                        $innerQuery->select(DB::raw(1))
                            ->from('tally_voucher_items')
                            ->whereColumn('tally_voucher_items.tally_voucher_id', 'tally_vouchers.id')
                            ->where('tally_voucher_items.stock_item_name', $itemGroupLedgerName);
                    });
            })
            ->get();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('total_sales', function ($data) use ($itemGroupLedgerName) {

                $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data){
                        $query->where('voucher_type', 'Sales');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->orderByDesc('id')
                    ->value('amount');
                    // ->sum('amount');

                $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data){
                        $query->where('voucher_type', 'Credit Note');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->orderByDesc('id')
                    ->value('amount');
                    // ->sum('amount');

                $totalSales = $stockItemVoucherSaleItem + $stockItemVoucherCreditNoteItem;

                return number_format($totalSales, 2);
            })
            ->addColumn('qty_sold', function ($data) use ($itemGroupLedgerName) {

                $stockItemVoucherSaleItem = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data) {
                        $query->where('voucher_type', 'Sales');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->count();

                $stockItemVoucherCreditNoteItem = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data) {
                        $query->where('voucher_type', 'Credit Note');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->count();

                $CustomerCount = $stockItemVoucherSaleItem + $stockItemVoucherCreditNoteItem;

                return number_format($CustomerCount, 2);
            })
            ->addColumn('avg_sales', function ($data) use ($itemGroupLedgerName) {

                $stockItemAmount = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data){
                        $query->where('voucher_type', 'Sales');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->orderByDesc('id')
                    ->value('amount');

                $stockItemQty = TallyVoucherItem::where('stock_item_name', $itemGroupLedgerName)
                    ->whereHas('tallyVoucher', function ($query) use ($data){
                        $query->where('voucher_type', 'Sales');
                        $query->where('party_ledger_name', $data->name);
                    })
                    ->orderByDesc('id')
                    ->value('billed_qty');
                $AvgSales = $stockItemAmount / $stockItemQty;
                return number_format($AvgSales, 2);
            })
            ->make(true);
    }

}