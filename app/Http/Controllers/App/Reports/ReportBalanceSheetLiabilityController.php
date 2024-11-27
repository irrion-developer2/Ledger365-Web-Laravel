<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class ReportBalanceSheetLiabilityController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function AllLiabilityReports($liabilityId)
    {
        $companyGuids = $this->reportService->companyData();

        $company = TallyCompany::where('guid', $companyGuids)->first();

        $liability = TallyLedgerGroup::where('guid', $liabilityId)
                                ->whereIn('company_guid', $companyGuids)
                                ->firstOrFail();

        return view('app.reports.balanceSheet.liability.index', [
            'liability' => $liability,
            'liabilityId' => $liabilityId,
            'company' => $company,
        ]);
    }

    public function getLiabilityData($liabilityId)
    {
        $companyGuids = $this->reportService->companyData();

        $generalLedger = TallyLedgerGroup::where('guid', $liabilityId)
                                    ->whereIn('company_guid', $companyGuids)
                                    ->firstOrFail();
    
        $data = collect();
    
        if ($generalLedger) {
            $query = TallyLedgerGroup::select(
                'tally_ledger_groups.id',
                'tally_ledger_groups.name',
                \DB::raw('COUNT(tally_ledgers.id) as ledgers_count'),
                \DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) as total_debit'),
                \DB::raw('SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END) as total_credit'),
                \DB::raw('tally_ledgers.opening_balance'),
                \DB::raw('(tally_ledgers.opening_balance + 
                            SUM(CASE WHEN tally_voucher_heads.entry_type = "debit" THEN tally_voucher_heads.amount ELSE 0 END) + 
                            SUM(CASE WHEN tally_voucher_heads.entry_type = "credit" THEN tally_voucher_heads.amount ELSE 0 END)) as closing_balance')
            )
            ->leftJoin('tally_ledgers', 'tally_ledger_groups.name', '=', 'tally_ledgers.parent')
            ->leftJoin('tally_voucher_heads', 'tally_ledgers.guid', '=', 'tally_voucher_heads.ledger_guid')
            ->leftJoin('tally_vouchers', 'tally_voucher_heads.tally_voucher_id', '=', 'tally_vouchers.id')
            ->where('tally_ledger_groups.parent', $generalLedger->name)
            ->whereIn('tally_ledger_groups.company_guid', $companyGuids)
            ->whereNot('tally_vouchers.is_cancelled', 'Yes')
            ->whereNot('tally_vouchers.is_optional', 'Yes')
            ->whereIn('tally_vouchers.company_guid', $companyGuids)
            ->groupBy('tally_ledger_groups.id', 'tally_ledger_groups.name', 'tally_ledgers.opening_balance')
            ->get();
    
            if (!$query->isEmpty()) {
                $data = $query;
            }
        }
    
        // If $data is still empty, set default data
        if ($data->isEmpty()) {
            $data = collect([
                (object)[
                    'id' => $liabilityId,
                    'name' => $generalLedger->name,
                    'opening_balance' => 0,
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'closing_balance' => 0,
                    'created_at' => now(),
                ]
            ]);
        }
    
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('total_debit', function ($row) {
                return $this->reportService->formatNumber($row->total_debit);
            })
            ->editColumn('total_credit', function ($row) {
                return $this->reportService->formatNumber($row->total_credit);
            })
            ->editColumn('opening_balance', function ($row) {
                return $this->reportService->formatNumber($row->opening_balance);
            })
            ->editColumn('closing_balance', function ($row) {
                return $this->reportService->formatNumber($row->closing_balance);
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d H:i:s');
            })
            ->make(true);
    }


}