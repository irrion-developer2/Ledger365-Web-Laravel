<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use App\DataTables\SuperAdmin\ReceiptRegisterDataTable;

class ReportReceiptRegisterController extends Controller
{

    public function index(ReceiptRegisterDataTable $dataTable)
    {
        return $dataTable->render('app.reports.receiptRegister.index');
    }

}
