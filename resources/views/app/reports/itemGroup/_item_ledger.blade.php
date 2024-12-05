@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
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
                        <li class="breadcrumb-item active" aria-current="page">Sales by Item Details</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;"  onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> Sales by Items</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.ItemLedger', ['ItemLedger' => $item->item_id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('ItemLedger') == $item->item_id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                    <span>{{ $item->item_name }}</span>
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
                        <h4 class="my-1 text-info">{{ $itemLedger->item_name }} </h4>
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="stripe row-border order-column" id="item-ledger-datatable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction Type</th>
                                        <th>Transaction</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data will be populated by AJAX --}}
                                </tbody>
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
@push('javascript')
<script>
	new PerfectScrollbar('.email-navigation');
	new PerfectScrollbar('.email-list');
</script>

@endpush
@section("script")
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {

        new DataTable('#item-ledger-datatable', {
            fixedColumns: {
                start: 1,
            },
            processing: true,
            serverSide: true,
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: '{{ route('reports.ItemLedger.get-data', $itemLedgerId) }}',
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {data: 'voucher_date', name: 'voucher_date'},
                {data: 'voucher_type_name', name: 'voucher_type_name'},
                { data: 'voucher_number', name: 'voucher_number', className: 'text-center',
                    {{--  render: function(data, type, row) {
                        return '<a href="{{ url('reports/VoucherItem') }}/' + row.voucher_id + '">' + data + '</a>';
                    }  --}}
                 },
                {data: 'debit', name: 'debit', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'credit', name: 'credit', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var DebitToTotal = 3;
                var CreditToTotal = 4;

                var Debittotal = api.column(DebitToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var Credittotal = api.column(CreditToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                $(api.column(DebitToTotal).footer()).html(jsIndianFormat(Math.abs(Debittotal), 2));
                $(api.column(CreditToTotal).footer()).html(jsIndianFormat(Math.abs(Credittotal), 2));
            },
            search: {
                orthogonal: {
                    search: 'plain' 
                }
            }
        });

        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

    });
</script>
@endsection