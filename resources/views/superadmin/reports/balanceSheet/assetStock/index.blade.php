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
                                    <li class="active text-white" aria-current="page">Current Asset</li>
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
                        <div class="col-md-8 d-flex align-items-center" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <h6>Particulars </h6>
                        </div>
                        <div class="col-md-4 text-center" style="border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            {{-- <span>{{ $liability->name }}</span><br> --}}
                            <b>{{ $company->name }} </b>
                            <hr>
                            <b>Closing Balance </b><br><br>

                            {{-- <div class="row mb-3">
                                <!-- Month-Year Filter -->
                                <div class="col-md-4">
                                    <label for="month-filter">Month-Year:</label>
                                    <input type="month" id="month-filter" class="form-control">
                                </div>
                                
                                <!-- Date Range Filter -->
                                <div class="col-md-4">
                                    <label for="date-range-filter">Date Range:</label>
                                    <input type="text" id="date-range-filter" class="form-control">
                                </div>
                            
                                <!-- Apply Filter Button -->
                                <div class="col-md-4">
                                    <button id="apply-filters" class="btn btn-primary mt-4">Apply Filters</button>
                                </div>
                            </div> --}}
                            
                        </div>

                        <div class="col-lg-12 col-md-12 col-sm-12 p-0" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            
                                <div class="table-responsive table-responsive-scroll border-0">
                                    <table id="balanceAssetStockSheet-datatable" class="stripe row-border order-column" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Debit</th>
                                                <th>Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- Data will be populated by AJAX --}}
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Grand Total</th>
                                                <th id="LiabilityDebit"></th>
                                                <th id="LiabilityCredit"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            

                        </div>


                        {{-- <div class="col-lg-12 col-md-12 col-sm-12 pt-2" style="border-right: 1px solid rgba(0, 0, 0, .125);border-bottom: 1px solid rgba(0, 0, 0, .125);">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6">
                                    <h6>Grand Total </h6>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 text-end">
                                    <h6 id="LiabilityDebit">Total </h6>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 text-end">
                                    <h6 id="LiabilityCredit">Total </h6>
                                </div>
                            </div>
                        </div> --}}


                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>

@endsection

@section("script")
@include('layouts.includes.datatable-js-css')
@include('superadmin.reports.balanceSheet.balance-Sheet-Script')

<script>
    $(document).ready(function() {
        var liabilityDebitCreditTable = $('#balanceAssetStockSheet-datatable').DataTable({
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: false,
            searching: false,
            order: false, 
            dom: '<"top"f>rt<"bottom"lp><"clear">', 
            ajax: {
                url: "{{ route('reports.BalanceSheetAssetStock.get-data') }}",
                type: 'GET'
            },
            columns: [
                {data: 'language_name', name: 'language_name'},
                { data: 'debit', name: 'debit', className: 'text-end' },
                { data: 'credit', name: 'credit', className: 'text-end' },
                { data: 'closing_balance', name: 'closing_balance', className: 'text-end' }
            ],
            initComplete: function () {
                calculateTotals();
            }
        });

        $('#apply-filters').on('click', function() {
            liabilityDebitCreditTable.ajax.reload(null, false);
        });

        $('#date-range-filter').flatpickr({
            mode: 'range',
            dateFormat: 'Y-m-d',
        });

        function calculateTotals() {

            var totalLiabilityDebit = 0;
            liabilityDebitCreditTable.rows().every(function () {
                var data = this.data();
                var totalDebit = parseFloat(data.total_debit.replace(/,/g, ''));
                if (!isNaN(totalDebit)) {
                    totalLiabilityDebit += totalDebit;
                }
            });


            var totalLiabilityCredit = 0;
            liabilityDebitCreditTable.rows().every(function () {
                var data = this.data();
                var totalCredit = parseFloat(data.total_credit.replace(/,/g, ''));
                if (!isNaN(totalCredit)) {
                    totalLiabilityCredit += totalCredit;
                }
            });
        
            
            $('#LiabilityDebit').text(Math.abs(totalLiabilityDebit).toFixed(3));
            $('#LiabilityCredit').text(Math.abs(totalLiabilityCredit).toFixed(3));
        }

    });
</script>


@endsection
