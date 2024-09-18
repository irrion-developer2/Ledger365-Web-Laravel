<?php

namespace App\Http\Controllers\SuperAdmin;

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
        return view ('superadmin.columnar.index');
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData(); 
    
        if ($request->ajax()) {
            $query = TallyVoucher::where('voucher_type', 'Sales')
                ->whereIn('company_guid', $companyGuids);
            
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = $request->input('start_date');
                    $endDate = $request->input('end_date');
        
                    if ($startDate && $endDate) {
                        try {
                            $startDate = new \DateTime($startDate);
                            $endDate = new \DateTime($endDate);
                            $endDate->setTime(23, 59, 59); // Set to end of day
        
                            $query->whereBetween('voucher_date', [$startDate, $endDate]);
                        } catch (\Exception $e) {
                            // Handle date parsing exceptions
                        }
                    }
                }

            if ($request->has('voucher_type')) {
                $voucherType = $request->input('voucher_type');
                if ($voucherType) {
                    $query->where('voucher_type', $voucherType);
                }
            }

            // if ($request->has('search') && $request->input('search')) {
            //     $search = $request->input('search')['value'] ?? null;
            //     if ($search) {
            //         $query->where(function($q) use ($search) {
            //             $q->where('voucher_number', 'like', "%{$search}%")
            //               ->orWhere('buyer_name', 'like', "%{$search}%")
            //               ->orWhere('buyer_addr', 'like', "%{$search}%")
            //               ->orWhere('state', 'like', "%{$search}%")
            //               ->orWhere('country', 'like', "%{$search}%")
            //               ->orWhere('buyer_gstin', 'like', "%{$search}%")
            //               ->orWhere('gst_registration_type', 'like', "%{$search}%")
            //               ->orWhere('place_of_supply', 'like', "%{$search}%");
            //         });
            //     }
            // }
    
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('state', function ($data) use ($companyGuids){
                    $ledger = TallyLedger::where('guid', $data->ledger_guid)
                        ->whereIn('company_guid', $companyGuids)
                        ->first();
        
                    return $ledger ? $ledger->state : '-';
                })
                ->addColumn('country', function ($data) use ($companyGuids){
                    $ledger = TallyLedger::where('guid', $data->ledger_guid)
                        ->whereIn('company_guid', $companyGuids)
                        ->first();
        
                    return $ledger ? $ledger->country : '-';
                })
                ->addColumn('qty', function ($data) use ($companyGuids){
                    $qtyItems = TallyVoucherItem::where('tally_voucher_id', $data->id)->get();
                    $totalQty = $qtyItems->sum('billed_qty');
                
                    return $totalQty > 0 ? number_format($totalQty, 3) : '0.00';
                })
                // ->addColumn('taxable_value', function ($data) use ($companyGuids){
                //     $qtyItems = TallyVoucherItem::where('tally_voucher_id', $data->id)->get();
                //     $totalQty = $qtyItems->sum('amount');
                
                //     return $totalQty > 0 ? number_format($totalQty, 3) : '0.00';
                // })
                ->addColumn('taxable_value', function ($data) use ($companyGuids){
                    $excludedLedgerNames = is_array($data->party_ledger_name) 
                        ? $data->party_ledger_name 
                        : explode(',', $data->party_ledger_name); 
                    $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));


                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Sales Accounts']);
                        // $query->whereNotIn('ledger_name', $excludedLedgerNames);
                    })
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    // dd($amtHead);
                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Sales Accounts']);
                        // ->whereNotIn('ledger_name', $excludedLedgerNames);
                    })
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount + $totalAccHeadAmount;
                
                    return number_format($totalAmount, 3);
                })
                ->addColumn('gross_total', function ($data) use ($companyGuids){
                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->where('entry_type', 'debit')
                    ->where('ledger_name', $data->party_ledger_name)
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->where('entry_type', 'debit')
                    ->where('ledger_name', $data->party_ledger_name)
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount - $totalAccHeadAmount;
                
                    return number_format(abs($totalAmount), 3);
                })
                ->addColumn('rate_unit', function ($data) use ($companyGuids){
                    $rateItems = TallyVoucherItem::where('tally_voucher_id', $data->id)->get();
                    $totalRate = $rateItems->sum('rate');
                
                    return $totalRate > 0 ? number_format($totalRate, 3) : '0.00';
                }) 
                ->addColumn('duties_taxes', function ($data) {
                    $ledgerHeads = TallyVoucherHead::where('tally_voucher_id', $data->id)
                        ->whereHas('ledger', function ($query) {
                            $query->where('parent', 'Indirect Expenses');
                        })
                        ->get();
                    $ledgerAccAllHeads = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                        ->get();
                    $ledgerData = [];
                
                    foreach ($ledgerHeads as $ledgerHead) {
                        $ledgerData[] = $ledgerHead->ledger_name;
                    }
                
                    foreach ($ledgerAccAllHeads as $ledgerAccHead) {
                        $ledgerData[] = $ledgerAccHead->ledger_name;
                    }
                    $ledgerData = array_unique($ledgerData);
                
                    return !empty($ledgerData) ? implode(', ', $ledgerData) : 'N/A';
                    return 'N/A';
                })
                ->addColumn('duties_taxes_amount', function ($data) {
                    $excludedLedgerNames = is_array($data->party_ledger_name) 
                        ? $data->party_ledger_name 
                        : explode(',', $data->party_ledger_name); 
                    $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));

                    $ledgerHeads = TallyVoucherHead::where('tally_voucher_id', $data->id)
                        ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                            $query->whereNotIn('ledger_name', $excludedLedgerNames);
                        })
                        ->get();
                
                    $ledgerAccAllHeads = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                        ->get();
                
                    $ledgerData = [];
                    $ledgerSums = [];
                
                    foreach ($ledgerHeads as $ledgerHead) {
                        $name = $ledgerHead->ledger_name;
                        $amount = $ledgerHead->amount;
                
                        if (!isset($ledgerSums[$name])) {
                            $ledgerSums[$name] = 0;
                        }
                        $ledgerSums[$name] += $amount;
                    }
                
                    foreach ($ledgerAccAllHeads as $ledgerAccHead) {
                        $name = $ledgerAccHead->ledger_name;
                        $amount = $ledgerAccHead->amount;
                
                        if (!isset($ledgerSums[$name])) {
                            $ledgerSums[$name] = 0;
                        }
                        $ledgerSums[$name] += $amount;
                    }
                
                    $output = [];
                    foreach ($ledgerSums as $name => $sum) {
                        $output[] = $name . ': ' . number_format($sum, 3);
                    }
                
                    return !empty($output) ? implode(', ', $output) : 'N/A';
                })
                ->addColumn('igst', function ($data) use ($companyGuids){
                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['IGST']);
                    })
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['IGST']);
                    })
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount + $totalAccHeadAmount;
                
                    return number_format(abs($totalAmount), 3);
                })
                ->addColumn('sgst', function ($data) use ($companyGuids){
                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['SGST/UTGST']);
                    })
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['SGST/UTGST']);
                    })
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount + $totalAccHeadAmount;
                
                    return number_format(abs($totalAmount), 3);
                })
                ->addColumn('cgst', function ($data) use ($companyGuids){
                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['CGST']);
                    })
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) {
                        $query->whereIn('gst_duty_head', ['CGST']);
                    })
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount + $totalAccHeadAmount;
                
                    return number_format(abs($totalAmount), 3);
                })
                ->addColumn('round_off', function ($data) use ($companyGuids){
                    $excludedLedgerNames = is_array($data->party_ledger_name) 
                        ? $data->party_ledger_name 
                        : explode(',', $data->party_ledger_name); 
                    $excludedLedgerNames = array_filter(array_map('trim', $excludedLedgerNames));


                    $amtHead = TallyVoucherHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Indirect Expenses']);
                        $query->whereNotIn('ledger_name', $excludedLedgerNames);
                    })
                    ->get();
                    $totalHeadAmount = $amtHead->sum('amount');

                    // dd($amtHead);
                    $amtAccHead = TallyVoucherAccAllocationHead::where('tally_voucher_id', $data->id)
                    ->whereHas('ledger', function ($query) use ($excludedLedgerNames) {
                        $query->whereIn('parent', ['Indirect Expenses'])
                        ->whereNotIn('ledger_name', $excludedLedgerNames);
                    })
                    ->get();
                    $totalAccHeadAmount = $amtAccHead->sum('amount');

                    $totalAmount = $totalHeadAmount + $totalAccHeadAmount;
                
                    return number_format($totalAmount, 3);
                })
                ->make(true);
        }
    }
    

}