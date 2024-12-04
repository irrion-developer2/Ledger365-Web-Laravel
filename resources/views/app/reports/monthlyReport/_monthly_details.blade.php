@extends("layouts.main")
@section('title', __('Monthly Details | PreciseCA'))

@section("style")
    <link href="https://unpkg.com/vue2-datepicker@3.10.2/index.css" rel="stylesheet">
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Report</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Monthly Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Monthly {{ $voucherTypeName }} Detail for 
                        @php
                            $monthName = date("F", mktime(0, 0, 0, $month, 10));
                        @endphp
                        {{ $monthName }} {{ $year }} - {{ $company->company_name ?? 'Unknown Company' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive table-responsive-scroll border-0">
                        <table id="monthlyDetail-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Voucher ID</th>
                                    <th>Voucher Date</th>
                                    <th>Entry Type</th>
                                    <th>Amount</th>
                                    <!-- Add more columns as needed -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyDetail as $detail)
                                    <tr>
                                        <td>{{ $detail->voucher_id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($detail->voucher_date)->format('Y-m-d') }}</td>
                                        <td>{{ ucfirst($detail->entry_type) }}</td>
                                        <td class="text-end">{{ $detail->amount }}</td>
                                        <!-- Add more columns as needed -->
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>Total</th>
                                    <th class="text-end">
                                        {{ indian_format(collect($monthlyDetail)->sum('amount')) }}
                                    </th>
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

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://unpkg.com/vue2-datepicker@3.10.2/index.min.js"></script>
    <script src="{{ url('assets/js/NumberFormatter.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#monthlyDetail-datatable').DataTable({
                fixedColumns: { start: 1 },
                processing: true,
                serverSide: false,
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: 300,
                columns: [
                    {data: 'voucher_id', name: 'voucher_id'},
                    {data: 'voucher_date', name: 'voucher_date'},
                    {data: 'entry_type', name: 'entry_type'},
                    {data: 'amount', name: 'amount', className: 'text-end', render: function(data) {
                        return data ? parseFloat(data).toFixed(2) : '-';
                    }},
                ],
                footerCallback: function ( row, data, start, end, display ) {
                    var api = this.api();

                    var total = api
                        .column(3)
                        .data()
                        .reduce(function (a, b) {
                            return parseFloat(a) + parseFloat(b);
                        }, 0);

                    $(api.column(3).footer()).html(
                        total.toFixed(2)
                    );
                }
            });
        });
    </script>
@endsection
