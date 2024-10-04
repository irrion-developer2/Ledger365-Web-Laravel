@extends("layouts.main")
@section('title', __('Balance Sheet | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="p-0 page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2" style="background-color: #0dcaf0;">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <div class="col-lg-12 col-md-12 col-sm-12">
                                    <div class="row m-0">
                                        <div class="col-lg-4 col-md-4 col-sm-4">
                                            <li class="active text-white" aria-current="page">Profit & Loss A/c</li>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-4 text-center">
                                            <li class="active text-white" aria-current="page">{{ $company->name }}</li>
                                        </div>
                                        <div class="col-lg-4 col-md-4 col-sm-4 text-end">
                                            <li class="active text-white" aria-current="page">
                                                <a href="javascript:;" class="text-white" onclick="history.back();">
                                                    <i class='fadeIn animated bx bx-x'></i>
                                                </a>
                                            </li>
                                        </div>
                                    </div>
                                </div>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <h6>Particulars </h6>
                        </div>
                        <div class="col-md-6" style="border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <h6>Particulars </h6>
                        </div>

                        <div class="col-md-6 p-0" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            
                            @include('app.reports.balanceSheet.dataTable.OpeningStock')

                            @include('app.reports.balanceSheet.dataTable.Expense')
                            
                        </div>

                        <div class="col-md-6" style="border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            
                            @include('app.reports.balanceSheet.dataTable.ClosingStock')

                            @include('app.reports.balanceSheet.dataTable.Revenue')
                        </div>

                        <div class="col-md-6 pt-2" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total </h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 id="diffExpense">Total </h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 pt-2" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total </h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 id="diffRevenue">Total </h6>
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
@include('app.reports.balanceSheet.balance-Sheet-Script')
@endsection
