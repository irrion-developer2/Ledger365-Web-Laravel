@extends("layouts.main")
@section('title', __('Balance Sheet | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="p-0 page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2" style="background-color: #0dcaf0;">
                {{-- <div class="breadcrumb-title pe-3">Balance Sheet</div> --}}
                <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="row m-0">
                                <div class="col-lg-4 col-md-4 col-sm-4">
                                    <li class="active text-white" aria-current="page">Balance Sheet</li>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4 text-center">
                                        <li class="active text-white" aria-current="page">{{ $company->name }}</li>
                                </div>                                
                                <div class="col-lg-4 col-md-4 col-sm-4 text-end">
                                    <li class="active text-white" aria-current="page"><a href="javascript:;" class="text-white" onclick="history.back();"><i class='fadeIn animated bx bx-x'></i></a></li>
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
                            <h6>Liabilities </h6>
                        </div>
                        <div class="col-md-6" style="border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <h6>Assets </h6>
                        </div>

                        <div class="col-md-6 p-0" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            
                                @include('app.reports.balanceSheet.dataTable.Liability')
                            

                        </div>
                        <div class="col-md-6" style="border-bottom: 1px solid rgba(0, 0, 0, .125);">

                            <div class="table-responsive table-responsive-scroll border-0">
                                <table id="balanceAssetSheet-datatable" class="stripe row-border order-column" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="">
                                        {{-- Data will be populated by AJAX --}}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Asset + Stock Items</th>
                                            <th id="AssetStockItem"></th>
                                        </tr>
                                        <tr>
                                            <th><a href="{{ route('reports.BalanceSheetProfitLoss') }}" style="color: #4c5258;">Profit & Loss A/c</a></th>
                                            <th id="ProfitLossAccAsset"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>


                        <div class="col-md-6 pt-2" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total </h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 id="Liability">Total </h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 pt-2" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total </h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 id="Asset">Total </h6>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>

    <span class="d-none">
        @include('app.reports.balanceSheet.dataTable.Expense')
        @include('app.reports.balanceSheet.dataTable.OpeningStock')
        @include('app.reports.balanceSheet.dataTable.ClosingStock')
        @include('app.reports.balanceSheet.dataTable.Revenue')
    </span>

@endsection

@section("script")
@include('layouts.includes.datatable-js-css')
@include('app.reports.balanceSheet.balance-Sheet-Script')
@endsection
