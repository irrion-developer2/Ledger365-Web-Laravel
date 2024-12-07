@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection
@php
    $currentMonth = date('m');
    $currentYear = date('Y');

    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
@endphp
@section("wrapper")
    <div class="page-wrapper">
            <div class="page-content p-2">
                        <!--breadcrumb-->
                        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                            <div class="breadcrumb-title pe-3">Reports</div>
                            <div class="ps-3">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0 p-0">
                                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">Analyse, Strategise and Grow with Reports</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <!--end breadcrumb-->

                        <div class="card" style="background: none;">
                            <div class="card-body">
                                <div class="row row-cols-1 row-cols-md-1 row-cols-xl-2">

                                    <div class="col">
                                        <div class="card radius-10 border-start border-0 border-4 border-info">
                                            <div class="card-body">
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            <h4 class="my-1 text-info">Accounting</h4>
                                                            <p class="mb-0 font-13">Get information about your accounting activities</p>
                                                        </div>
                                                        <div class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i class='bx bxs-cart'></i>
                                                        </div>
                                                    </div>
                                                    <div class="pt-4">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('reports.daybook') }}">Daybook</a></h5>
                                                        <hr class="border-1">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('reports.GroupSummary') }}">Group Summary</a></h5>
                                                        <hr class="border-1">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('reports.LedgerSummary') }}">Ledger Summary</a></h5>
                                                        <hr class="border-1">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('reports.PaymentRegister') }}">Payment Register</a></h5>
                                                        <hr class="border-1">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('reports.ReceiptRegister') }}">Receipt Register</a></h5>
                                                        <hr class="border-1">
                                                        <h5 class="my-1"><a class="nav-link " href="{{ route('otherLedgers.index') }}">Other Ledgers</a></h5>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="card radius-10 border-start border-0 border-4 border-success">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h4 class="my-1 text-success">Sales and Outstanding</h4>
                                                    <p class="mb-0 font-13">Find how much your customers owe</p>
                                                </div>
                                                <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class='bx bx-money'></i>
                                                </div>
                                            </div>
                                            <div class="pt-4">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.MonthlySales') }}">Monthly Sales</a></h5>
                                                <hr class="border-1">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.MonthlyPurchase') }}">Monthly Purchase</a></h5>
                                                <hr class="border-1">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.CustomerGroup') }}">Sales by Customers Group</a></h5>
                                                <hr class="border-1">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.ItemGroup') }}">Sales by Items Group</a></h5>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="card radius-10 border-start border-0 border-4 border-warning">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h4 class="my-1 text-warning">Inventory</h4>
                                                    <p class="mb-0 font-13">Manage healthy stock levels</p>
                                                </div>
                                                <div class="widgets-icons-2 rounded-circle bg-gradient-orange text-white ms-auto"><i class='bx bxs-cart'></i>
                                                </div>
                                            </div>
                                            <div class="pt-4">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('stock-items.index') }}">Stock Items</a></h5>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="card radius-10 border-start border-0 border-4 border-success">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h4 class="my-1 text-success">Financial Reports</h4>
                                                    <p class="mb-0 font-13">Get Information about your Financial Details</p>
                                                </div>
                                                <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class='lni lni-stats-up'></i>
                                                </div>
                                            </div>
                                            <div class="pt-4">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.BalanceSheet') }}">Balance Sheet</a></h5>
                                                <hr class="border-1">
                                                <h5 class="my-1"><a class="nav-link " href="{{ route('reports.BalanceSheetProfitLoss') }}">Profit & Loss</a></h5>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="card radius-10 border-start border-0 border-4 border-warning">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h4 class="my-1 text-warning">Other Reports</h4>
                                                    </div>
                                                    <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class='bx bxs-wallet'></i>
                                                    </div>
                                                </div>
                                                <div class="pt-4">
                                                    <h5 class="my-1"><a class="nav-link " href="{{ route('reports.cancelled') }}">Cancelled Records</a></h5>
                                                    <hr class="border-1">
                                                    <h5 class="my-1"><a class="nav-link " href="{{ route('reports.optional') }}">Optional Records</a></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>


            </div>
    </div>
@endsection

@section("script")
@include('layouts.includes.datatable-js-css')
@endsection
