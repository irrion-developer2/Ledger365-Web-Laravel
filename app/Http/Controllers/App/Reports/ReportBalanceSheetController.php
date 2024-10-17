<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Services\ReportService;
use App\Models\TallyLedger;
use App\Models\TallyLedgerGroup;
use App\Models\TallyVoucherHead;
use App\Models\TallyVoucher;
use App\Models\TallyVoucherItem;
use App\Models\TallyItem;
use App\Models\TallyCompany;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportBalanceSheetController extends Controller
{

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $companyGuids = $this->reportService->companyData();

        $company = TallyCompany::where('guid', $companyGuids)->first();

        return view ('app.reports.balanceSheet.index', compact('company'));
    }
    
    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $startTime = microtime(true);

            $accountTypeCases = '
                CASE 
                    WHEN name LIKE "%liabilities%" THEN "Liability"
                    WHEN name LIKE "%liability%" THEN "Liability"
                    WHEN name LIKE "%branch / divisions%" THEN "Liability"
                    WHEN name LIKE "%suspense a/c%" THEN "Liability"
                    WHEN name LIKE "%capital account%" THEN "Liability"

                    WHEN name LIKE "%assets%" THEN "Asset"
                    WHEN name LIKE "%asset%" THEN "Asset"
                    WHEN name LIKE "%investments%" THEN "Asset"

                    WHEN name LIKE "%income%" THEN "Revenue"
                    WHEN name LIKE "%revenue%" THEN "Revenue"
                    WHEN name LIKE "%sales accounts%" THEN "Revenue"

                    WHEN name LIKE "%expense%" THEN "Expense"
                    WHEN name LIKE "%purchase%" THEN "Expense"

                    ELSE "Other" 
                END as account_type
            ';

            $Balancequery = TallyLedgerGroup::where(function($query) {
                            $query->where('parent', '')->orWhereNull('parent');
                        })
                        ->whereIn('company_guid', $companyGuids)
                        ->selectRaw("guid, name, parent, company_guid, $accountTypeCases");
    
            Log::info("BalanceSheet Query");        
            Log::info($this->reportService->getFinalQuery($Balancequery));
    
            $endTime1 = microtime(true);
            $executionTime1 = $endTime1 - $startTime;
            Log::info('Total first db request execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime1 . ' seconds']);
    
            $dataTable = DataTables::of($Balancequery)
                ->addIndexColumn()
                ->editColumn('amount', function ($data) use ($companyGuids) {

                    $name = $data->name;

                    foreach ($this->reportService->normalizedNames as $pattern => $normalized) {
                        if (strpos($name, $pattern) !== false) {
                            $name = $normalized;
                            break;
                        }
                    }

                    $groupLedgerIdsQuery = TallyLedgerGroup::where('parent', $name)->whereIn('company_guid', $companyGuids);
                    $groupLedgerIds = $groupLedgerIdsQuery->pluck('name');
    
                    if ($groupLedgerIds->isNotEmpty()) {
                        $ledgerIds = TallyLedger::whereIn('parent', $groupLedgerIds)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    } else {
                        $ledgerIds = TallyLedger::where('parent', $name)
                                ->whereIn('company_guid', $companyGuids)
                                ->pluck('guid');
                    }
    
                    $allLedgerIds = $ledgerIds->unique();
    
                    if ($allLedgerIds->isEmpty()) {
                        return '-';
                    }
    
                    $totalAmount = TallyVoucherHead::join('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
                                                    ->whereIn('tally_voucher_heads.ledger_guid', $allLedgerIds)
                                                    ->whereNot('tally_vouchers.is_cancelled', 'Yes')
                                                    ->whereNot('tally_vouchers.is_optional', 'Yes')
                                                    ->sum('tally_voucher_heads.amount');

                    if ($totalAmount == 0) {
                        return '-';
                    }
    

                    // dd($totalAmount);
                    return indian_format(abs($totalAmount));
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'liabilities') !== false || strpos($name, 'liability') !== false;
                    });
                })
                ->filter(function ($query) {
                    $query->get()->filter(function ($item) {
                        $name = strtolower($item->name);
                        return strpos($name, 'assets') !== false || strpos($name, 'asset') !== false;
                    });
                })

                ->make(true);
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::info('Total end execution time for ReportBalanceSheetController.getDATA:', ['time_taken' => $executionTime . ' seconds']);
    
            return $dataTable;
        }
    }

}
