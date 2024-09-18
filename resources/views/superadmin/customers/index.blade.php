@extends("layouts.main")
@section('title', __('Customers | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Customers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Customers</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">


                    <div class="d-lg-flex align-items-center gap-2">
                        <button id="filter-outstanding" class="btn btn-outline-secondary p-1">Outstanding</button>
                
                        <button id="filter-ageing" class="btn btn-outline-secondary p-1">Overdue</button>
                    
                        <button id="filter-collection" class="btn btn-outline-secondary p-1">Collections</button>

                        <button id="filter-sale" class="btn btn-outline-secondary p-1">No Sales</button>
                    </div>
                    
                    <div class="table-responsive table-responsive-scroll border-0">
                        
                        <table id="customer-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    {{-- <th>Id</th> --}}
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>
                                        Sales
                                        <br>
                                        <span style="font-size: smaller;color: gray;">(Last 30 days)</span>
                                    </th>
                                    <th>Net ₹ Due</th>
                                    <th>₹ Overdue</th>
                                    <th>
                                        ₹ Pmt Collection
                                        <br>
                                        <span style="font-size: smaller;color: gray;">FY</span>
                                    </th>
                                    <th>
                                        Last Payment
                                    </th>
                                    <th>₹ Credit Limit</th>
                                    <th>₹ Credit Period</th>
                                    {{-- <th>GSTIN</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    {{-- <th>Id</th> --}}
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>
                                        Sales
                                        <br>
                                        <span style="font-size: smaller;color: gray;">(Last 30 days)</span>
                                    </th>
                                    <th>Net ₹ Due</th>
                                    <th>₹ Overdue</th>
                                    <th>
                                        ₹ Pmt Collection
                                        <br>
                                        <span style="font-size: smaller;color: gray;">FY</span>
                                    </th>
                                    <th>
                                        Last Payment
                                    </th>
                                    <th>₹ Credit Limit</th>
                                    <th>₹ Credit Period</th>
                                    {{-- <th>GSTIN</th> --}}
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
<script>
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var filterOutstanding = urlParams.get('filter_outstanding') === 'true';
        var filterAgeing = urlParams.get('filter_ageing') === 'true';
        var filterCollection = urlParams.get('filter_collection') === 'true';
        var filterSale = urlParams.get('filter_sale') === 'true';

        var table = new DataTable('#customer-datatable', {
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('customers.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.filter_outstanding = filterOutstanding;
                    d.filter_ageing = filterAgeing;
                    d.filter_collection = filterCollection;
                    d.filter_sale = filterSale;
                }
            },
            columns: [
                {data: 'language_name', name: 'language_name',
                    render: function(data, type, row) {
                        var url = '{{ route("customers.show", ":guid") }}';
                        url = url.replace(':guid', row.guid);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                {data: 'party_gst_in', name: 'party_gst_in', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'sales_last_30_days', name: 'sales_last_30_days'},
                {data: 'outstanding', name: 'outstanding', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'overdue', name: 'overdue', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'payment_collection', name: 'payment_collection', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'payment_date', name: 'payment_date', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                // {data: 'phone_no', name: 'phone_no', render: function(data, type, row) {
                //     return data ? data : '-';
                // }},
                // {data: 'email', name: 'email', render: function(data, type, row) {
                //     return data ? data : '-';
                // }},
                {data: 'credit_limit', name: 'credit_limit', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'bill_credit_period', name: 'bill_credit_period', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                // {data: 'parent', name: 'parent'},
               
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var LastSaleToTotal = 2;
                var OutstandingToTotal = 3;
                var OverdueToTotal = 4;
                var PmtToTotal = 5;

                var LastSaletotal = api.column(LastSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Outstandingtotal = api.column(OutstandingToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Overduetotal = api.column(OverdueToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Pmttotal = api.column(PmtToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                $(api.column(LastSaleToTotal).footer()).html(number_format(Math.abs(LastSaletotal), 2));
                $(api.column(OutstandingToTotal).footer()).html(number_format(Math.abs(Outstandingtotal), 2));
                $(api.column(OverdueToTotal).footer()).html(number_format(Math.abs(Overduetotal), 2));
                $(api.column(PmtToTotal).footer()).html(number_format(Math.abs(Pmttotal), 2));
            },
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        $('#filter-outstanding').on('click', function () {
            filterOutstanding = !filterOutstanding;
            var newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_outstanding', filterOutstanding ? 'true' : 'false');
            window.location.href = newUrl.href;
        });

        $('#filter-ageing').on('click', function () {
            filterAgeing = !filterAgeing;
            var newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_ageing', filterAgeing ? 'true' : 'false');
            window.location.href = newUrl.href;
        });

        $('#filter-collection').on('click', function () {
            filterCollection = !filterCollection;
            var newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_collection', filterCollection ? 'true' : 'false');
            window.location.href = newUrl.href;
        });

        $('#filter-sale').on('click', function () {
            filterSale = !filterSale;
            var newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_sale', filterSale ? 'true' : 'false');
            window.location.href = newUrl.href;
        });

        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

        function number_format(number, decimals) {
            if (isNaN(number)) return 0;
            number = parseFloat(number).toFixed(decimals);
            var parts = number.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }
    });
</script>
@endsection
