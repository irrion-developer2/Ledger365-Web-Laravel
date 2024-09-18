<?php

namespace App\Http\Controllers\SuperAdmin\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use App\DataTables\SuperAdmin\PaymentRegisterDataTable;

class ReportPaymentRegisterController extends Controller
{

    public function index(PaymentRegisterDataTable $dataTable)
    {
        return $dataTable->render('superadmin.reports.paymentRegister.index');
    }

}
