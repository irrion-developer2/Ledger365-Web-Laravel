@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection
<style>
    .table-responsive-scroll {
        max-height: 500px; /* Set to your preferred height */
        overflow-y: auto;
        overflow-x: hidden !important; /* Optional, hides horizontal scrollbar */
        border: 1px solid #ddd;
    }
</style>
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content pt-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Reports</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">General Ledger</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;"  onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> General Ledger</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.GeneralLedger.details', ['GeneralLedger' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('GeneralLedger') == $item->id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                    <span>{{ $item->name }}</span>
                                    @if(isset($item->badge))
                                        <span class="badge bg-primary rounded-pill ms-auto">{{ $item->badge }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="email-header d-xl-flex align-items-center padding-0" style="height: auto;">
                
                <div class="d-flex align-items-center">
                    <div class="">
                        <h4 class="my-1 text-info">{{ $generalLedger->name }} </h4>
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="table table-striped" id="general-ledger-table" width="100%">
                                <thead>
                                    <tr>
                                        <td>Name</td>
                                        <td>Opening Balance</td>
                                        <td>Debit</td>
                                        <td>Credit</td>
                                        <td>Closing Balance</td>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        
            <!--start email overlay-->
            <div class="overlay email-toggle-btn-mobile"></div>
            <!--end email overlay-->
        </div>
        <!--end email wrapper-->


            
    </div>
</div>




@endsection
@push('css')
@include('layouts.includes.datatable-css')
@endpush
@push('javascript')
<script>
	new PerfectScrollbar('.email-navigation');
	new PerfectScrollbar('.email-list');
</script>
@include('layouts.includes.datatable-js')

<script>
        $(document).ready(function() {
        $('#general-ledger-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: {
                url: '{{ route('reports.GeneralLedger.data', $generalLedgerId) }}',
                dataSrc: function (json) {
                    console.log(json); // Debug the data structure here
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'name',
                    name: 'name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.GeneralGroupLedger.details", ":id") }}';
                        url = url.replace(':id', row.id || '{{ $generalLedgerId }}'); // Handle missing id
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                {
                    data: 'opening_balance',
                    name: 'opening_balance',
                    render: function(data) {
                        return data;
                    }
                },
                {
                    data: 'total_debit',
                    name: 'total_debit',
                    render: function(data) {
                        return data;
                    }
                },
                {
                    data: 'total_credit',
                    name: 'total_credit',
                    render: function(data) {
                        return data;
                    }
                },
                {
                    data: 'closing_balance',
                    name: 'closing_balance',
                    render: function(data) {
                        return data;
                    }
                }
            ],
            footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // Initialize totals
                    var totalOpeningBalance = 0;
                    var totalDebit = 0;
                    var totalCredit = 0;
                    var runningBalance = 0;

                    // Calculate totals
                    api.rows().every(function (rowIdx, tableLoop, rowLoop) {
                        var data = this.data();
                        var openingBalance = parseFloat(data.opening_balance.replace(/[^0-9.-]+/g, "")) || 0;
                        var debit = parseFloat(data.total_debit.replace(/[^0-9.-]+/g, "")) || 0;
                        var credit = parseFloat(data.total_credit.replace(/[^0-9.-]+/g, "")) || 0;

                        totalOpeningBalance += openingBalance;
                        totalDebit += debit;
                        totalCredit += credit;

                        runningBalance += openingBalance + debit - credit;
                    });

                    // Update footer values
                    $(api.column(1).footer()).html(totalOpeningBalance.toFixed(2));
                    $(api.column(2).footer()).html(totalDebit.toFixed(2));
                    $(api.column(3).footer()).html(totalCredit.toFixed(2));
                    $(api.column(4).footer()).html(Math.abs(runningBalance).toFixed(2));
                }

            // footerCallback: function (row, data, start, end, display) {
            //     var api = this.api();

            //     var totalOpeningBalance = api.column(1).data().reduce(function (a, b) {
            //         var num = parseFloat(b.replace(/[^0-9.-]+/g, ""));
            //         return isNaN(num) ? a : a + num;
            //     }, 0);
                
            //     var totalDebit = api.column(2).data().reduce(function (a, b) {
            //         var num = parseFloat(b.replace(/[^0-9.-]+/g, ""));
            //         return isNaN(num) ? a : a + num;
            //     }, 0);
                
            //     var totalCredit = api.column(3).data().reduce(function (a, b) {
            //         var num = parseFloat(b.replace(/[^0-9.-]+/g, ""));
            //         return isNaN(num) ? a : a + num;
            //     }, 0);
                
            //     var totalClosingBalance = api.column(4).data().reduce(function (a, b) {
            //         var num = parseFloat(b.replace(/[^0-9.-]+/g, ""));
            //         return isNaN(num) ? a : a + num;
            //     }, 0);

            //     // Update footer values
            //     $(api.column(1).footer()).html(totalOpeningBalance.toFixed(2));
            //     $(api.column(2).footer()).html(totalDebit.toFixed(2));
            //     $(api.column(3).footer()).html(totalCredit.toFixed(2));
            //     $(api.column(4).footer()).html(totalClosingBalance.toFixed(2));
            // }
        });
    });
</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>

@endsection