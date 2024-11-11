<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\TallyCompany;
use App\Models\TallyVoucher;
use App\Models\TallyLedger;
use App\Models\TallyVoucherItem;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucherAccAllocationHead;
use App\Models\TallyBankAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\ReportService;

class ColumnarController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        return view ('app.columnar.index');
    }

    public function getData(Request $request)
    {
        $companyIds = $this->reportService->companyData();

        if ($request->ajax()) {
            
            $columnars = TallyVoucher::join('tally_voucher_types', 'tally_vouchers.voucher_type_id', '=', 'tally_voucher_types.voucher_type_id')
                ->join('tally_voucher_heads', 'tally_vouchers.voucher_id', '=', 'tally_voucher_heads.voucher_id')
                ->join('tally_ledgers', 'tally_voucher_heads.ledger_id', '=', 'tally_ledgers.ledger_id')
                ->where('tally_voucher_types.voucher_type_name', 'Sales')
                ->where('tally_vouchers.is_cancelled', 0)
                ->where('tally_vouchers.is_optional', 0)
                ->whereIn('tally_vouchers.company_id', $companyIds)
                ->selectRaw('COALESCE(SUM(CASE WHEN tally_voucher_types.voucher_type_name = "Sales" AND tally_ledgers.ledger_id = tally_voucher_heads.ledger_id AND tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount END), 0) as gross_total')
                ->select(
                    'tally_vouchers.voucher_date',
                    'tally_vouchers.voucher_number',
                    'tally_vouchers.buyer_name',
                    'tally_vouchers.buyer_addr',
                    'tally_vouchers.gst_registration_type',
                    'tally_vouchers.buyer_gstin',
                    'tally_vouchers.place_of_supply',
                    'tally_voucher_types.voucher_type_name',
                    'tally_ledgers.ledger_name',
                    'tally_ledgers.state',
                    'tally_ledgers.country',
                )
                ->get();

            // dd($columnars);

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
                $columnars->whereBetween('voucher_date', [$startDate, $endDate]);
            }

            return DataTables::of($columnars)
                ->addIndexColumn()
                // ->addColumn('gross_total', function ($data) use ($companyIds){
                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->where('entry_type', 'debit')
                //     ->where('ledger_name', $data->party_ledger_name)
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');

                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->where('entry_type', 'debit')
                //     ->where('ledger_name', $data->party_ledger_name)
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount - $totalAccHeadAmount;

                //     return number_format(abs($totalAmount), 3);
                // })
                // ->addColumn('qty', function ($data) use ($companyIds){
                //     $qtyItems = TallyVoucherItem::where('tally_voucher_id', $data->id)->get();
                //     $totalQty = $qtyItems->sum('billed_qty');

                //     return $totalQty > 0 ? number_format($totalQty, 3) : '0.00';
                // })
                // ->addColumn('taxable_value', function ($data) use ($companyIds){
                //     $excludedLedgerNames = is_array($data->party_ledger_name)
                //         ? $data->party_ledger_name
                //         : explode(',', $data->party_ledger_name);
                //     $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));


                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                //         $query->whereIn('parent', ['Sales Accounts']);
                //         // $query->whereNotIn('ledger_name', $excludedLedgerNames);
                //     })
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');
                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                //         $query->whereIn('parent', ['Sales Accounts']);
                //     })
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount + $totalAccHeadAmount;

                //     return number_format($totalAmount, 3);
                // })
                // ->addColumn('rate_unit', function ($data) use ($companyIds){
                //     $rateItems = TallyVoucherItem::where('tally_voucher_id', $data->id)->get();
                //     $totalRate = $rateItems->sum('rate');

                //     return $totalRate > 0 ? number_format($totalRate, 3) : '0.00';
                // })
                // ->addColumn('duties_taxes', function ($data) {
                //     $ledgerHeads = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //         ->whereHas('ledger', function ($query) {
                //             $query->where('parent', 'Indirect Expenses');
                //         })
                //         ->get();
                //     $ledgerAccAllHeads = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //         ->get();
                //     $ledgerData = [];

                //     foreach ($ledgerHeads as $ledgerHead) {
                //         $ledgerData[] = $ledgerHead->ledger_name;
                //     }

                //     foreach ($ledgerAccAllHeads as $ledgerAccHead) {
                //         $ledgerData[] = $ledgerAccHead->ledger_name;
                //     }
                //     $ledgerData = array_unique($ledgerData);

                //     return !empty($ledgerData) ? implode(', ', $ledgerData) : 'N/A';
                //     return 'N/A';
                // })
                // ->addColumn('duties_taxes_amount', function ($data) {
                //     $excludedLedgerNames = is_array($data->party_ledger_name)
                //         ? $data->party_ledger_name
                //         : explode(',', $data->party_ledger_name);
                //     $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));

                //     $ledgerHeads = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //         ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                //             $query->whereNotIn('ledger_name', $excludedLedgerNames);
                //         })
                //         ->get();

                //     $ledgerAccAllHeads = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //         ->get();

                //     $ledgerData = [];
                //     $ledgerSums = [];

                //     foreach ($ledgerHeads as $ledgerHead) {
                //         $name = $ledgerHead->ledger_name;
                //         $amount = $ledgerHead->amount;

                //         if (!isset($ledgerSums[$name])) {
                //             $ledgerSums[$name] = 0;
                //         }
                //         $ledgerSums[$name] += $amount;
                //     }

                //     foreach ($ledgerAccAllHeads as $ledgerAccHead) {
                //         $name = $ledgerAccHead->ledger_name;
                //         $amount = $ledgerAccHead->amount;

                //         if (!isset($ledgerSums[$name])) {
                //             $ledgerSums[$name] = 0;
                //         }
                //         $ledgerSums[$name] += $amount;
                //     }

                //     $output = [];
                //     foreach ($ledgerSums as $name => $sum) {
                //         $output[] = $name . ': ' . number_format($sum, 3);
                //     }

                //     return !empty($output) ? implode(', ', $output) : 'N/A';
                // })
                // ->addColumn('igst', function ($data) use ($companyIds){
                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['IGST']);
                //     })
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');

                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['IGST']);
                //     })
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount + $totalAccHeadAmount;

                //     return number_format(abs($totalAmount), 3);
                // })
                // ->addColumn('sgst', function ($data) use ($companyIds){
                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['SGST/UTGST']);
                //     })
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');

                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['SGST/UTGST']);
                //     })
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount + $totalAccHeadAmount;

                //     return number_format(abs($totalAmount), 3);
                // })
                // ->addColumn('cgst', function ($data) use ($companyIds){
                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['CGST']);
                //     })
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');

                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) {
                //         $query->whereIn('gst_duty_head', ['CGST']);
                //     })
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount + $totalAccHeadAmount;

                //     return number_format(abs($totalAmount), 3);
                // })
                // ->addColumn('round_off', function ($data) use ($companyIds){
                //     $excludedLedgerNames = is_array($data->party_ledger_name)
                //         ? $data->party_ledger_name
                //         : explode(',', $data->party_ledger_name);
                //     $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));


                //     $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                //         $query->whereIn('parent', ['Indirect Expenses']);
                //         $query->whereNotIn('ledger_name', $excludedLedgerNames);
                //     })
                //     ->get();
                //     $totalHeadAmount = $amtHead->sum('amount');

                //     // dd($amtHead);
                //     $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                //     ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                //         $query->whereIn('parent', ['Indirect Expenses'])
                //         ->whereNotIn('ledger_name', $excludedLedgerNames);
                //     })
                //     ->get();
                //     $totalAccHeadAmount = $amtAccHead->sum('amount');

                //     $totalAmount = $totalHeadAmount + $totalAccHeadAmount;

                //     return number_format($totalAmount, 3);
                // })
                ->make(true);
        }
    }

}
