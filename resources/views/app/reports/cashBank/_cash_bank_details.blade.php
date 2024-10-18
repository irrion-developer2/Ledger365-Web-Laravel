@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .table-responsive-scroll {
        max-height: 500px; /* Set to your preferred height */
        overflow-y: auto;
        overflow-x: hidden !important; /* Optional, hides horizontal scrollbar */
        border: 1px solid #ddd;
    }
</style>

@endsection
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
                        <li class="breadcrumb-item active" aria-current="page">Cash and Bank</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;" onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> Cash and Bank</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.CashBank.details', ['CashBank' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('CashBank') == $item->id ? 'active' : '' }}" style="border-top: none;">
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
                        <h4 class="my-1 text-info">{{ $cashBank->name }} </h4>
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="table table-striped" id="cash-bank-table" width="100%">
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
                                        <td>Total</td>
                                        <td id="footer-opening-balance"></td>
                                        <td id="footer-debit"></td>
                                        <td id="footer-credit"></td>
                                        <td id="footer-closing-balance"></td>
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
{{-- <script>
    $(document).ready(function() {
        $('#cash-bank-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: '{{ route('reports.CashBank.data', $cashBankId) }}',
            columns: [
                {
                    data: 'name',
                    name: 'name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.VoucherHead", ":guid") }}';
                        url = url.replace(':guid', row.guid);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                { data: 'parent', name: 'parent' },
                { data: 'parent', name: 'entry_type' },
                { data: 'parent', name: 'opening_balance' },
                { data: 'parent', name: 'debit' },
                { data: 'parent', name: 'credit' },
                { data: 'parent', name: 'closing_balance' }
            ]
        });
    });


</script> --}}

<script>
     $(document).ready(function() {
    $('#cash-bank-table').DataTable({
        processing: true,
        serverSide: true,
        paging: false,
        ajax: '{{ route('reports.CashBank.data', $cashBankId) }}',
        columns: [
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    var url = '{{ route("reports.VoucherHead", ":guid") }}';
                    url = url.replace(':guid', row.guid);
                    return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                }
            },
            { data: 'opening_balance', name: 'opening_balance', className: 'text-end' },
            { data: 'debit', name: 'debit', className: 'text-end' },
            { data: 'credit', name: 'credit', className: 'text-end' },
            { data: 'closing_balance', name: 'closing_balance', className: 'text-end' }
        ],
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();

            // Calculate totals
            var totalOpeningBalance = api.column(1, { page: 'current' }).data().reduce(function(a, b) {
                return (parseFloat(a) || 0) + (parseFloat(b) || 0);
            }, 0);

            var totalDebit = api.column(2, { page: 'current' }).data().reduce(function(a, b) {
                return (parseFloat(a) || 0) + (parseFloat(b) || 0);
            }, 0);

            var totalCredit = api.column(3, { page: 'current' }).data().reduce(function(a, b) {
                return (parseFloat(a) || 0) + (parseFloat(b) || 0);
            }, 0);

            var totalClosingBalance = totalOpeningBalance + totalDebit - totalCredit;

            // Update footer
            $(api.column(1).footer()).html(Math.abs(totalOpeningBalance).toFixed(2));
            $(api.column(2).footer()).html(Math.abs(totalDebit).toFixed(2));
            $(api.column(3).footer()).html(Math.abs(totalCredit).toFixed(2));
            $(api.column(4).footer()).html(Math.abs(totalClosingBalance).toFixed(2));
        }
    });
});

</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>

@endsection