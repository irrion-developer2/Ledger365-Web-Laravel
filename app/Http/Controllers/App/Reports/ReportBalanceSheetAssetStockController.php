<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\TallyLedger;
use App\Models\TallyGroup;
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

class ReportBalanceSheetAssetStockController extends Controller
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

        return view ('app.reports.balanceSheet.assetStock.index', compact('company'));
    }

    public function getData(Request $request)
    {
        $companyGuids = $this->reportService->companyData();

        if ($request->ajax()) {
            $openingValueSum = TallyItem::whereIn('company_guid', $companyGuids)->sum('opening_value');
            $data = [
                [
                    'name' => 'Opening Stock',
                    'opening_value' => number_format(abs($openingValueSum), 3)
                ]
            ];
            return response()->json(['data' => $data]);
        }
    }

}