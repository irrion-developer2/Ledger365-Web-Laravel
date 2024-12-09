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

            
                <div class="card">
                    <div class="card-body">
                        <div class="d-lg-flex align-items-center gap-2">
                            <h4 class="my-1 text-info">{{ $itemLedger->item_name }} </h4>
                        </div>

                        <div class="table-responsive table-responsive-scroll border-0">
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
</div>




@endsection
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