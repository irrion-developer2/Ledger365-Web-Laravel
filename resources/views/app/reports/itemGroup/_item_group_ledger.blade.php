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
                        <li class="breadcrumb-item active" aria-current="page">Sales by Items Group</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;"  onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> Sales by Items Group</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.ItemGroupLedger', ['ItemGroupLedger' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('ItemGroupLedger') == $item->id ? 'active' : '' }}" style="border-top: none;">
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
                        <h4 class="my-1 text-info">{{ $itemGroupLedger->name }} </h4>
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="stripe row-border order-column" id="item-group-ledger-datatable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Total Sales</th>
                                        <th>Qty Sold</th>
                                        <th>
                                            ₹ Avg Sales
                                            <br>
                                            <span style="font-size: smaller;color: gray;">Price</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data will be populated by AJAX --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Name</th>
                                        <th>Total Sales</th>
                                        <th>Qty Sold</th>
                                        <th>
                                            ₹ Avg Sales
                                            <br>
                                            <span style="font-size: smaller;color: gray;">Price</span>
                                        </th>
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


{{-- <script>
    $(document).ready(function() {
        $('#customer-group-ledger-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: true,
            ajax: '{{ route('reports.CustomerGroupLedger.data', $customerGroupLedgerId) }}',
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
                { data: 'total_sales', name: 'total_sales', className: 'text-end' },
                { data: 'percentage', name: 'percentage', className: 'text-end' },  // Adjusted to new column
                { data: 'total_count', name: 'total_count', className: 'text-end' },
                { data: 'avg_sales', name: 'avg_sales', className: 'text-end' },
                // { data: 'sales_count', name: 'sales_count', className: 'text-end' }
            ],
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Calculate totals
                var totalSales = api.column(1, { page: 'current' }).data().reduce(function(a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0);

                var totalPercentage = api.column(2, { page: 'current' }).data().reduce(function(a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0);

                var totalCount = api.column(3, { page: 'current' }).data().reduce(function(a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0);

                var totalAvgSales = api.column(4, { page: 'current' }).data().reduce(function(a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0);

                var totalSalesCount = data.reduce(function(total, item) {
                    return total + (parseInt(item.sales_count, 10) || 0);
                }, 0);

                var AvgSales = totalSalesCount > 0 ? totalSales / totalSalesCount : 0;

                // Update footer
                $(api.column(3).footer()).html(Math.abs(totalCount).toFixed(2));
                $(api.column(1).footer()).html(Math.abs(totalSales).toFixed(2));
                $(api.column(2).footer()).html(Math.abs(totalPercentage / data.length).toFixed(2) + '%'); // Average percentage
                $(api.column(4).footer()).html(Math.abs(AvgSales).toFixed(2));

                // Update percentage column
                api.column(2).nodes().each(function(cell, i) {
                    var rowData = api.row(i).data();
                    var percentage = totalSales > 0 ? (parseFloat(rowData.total_sales) / totalSales * 100).toFixed(2) : '0.00';
                    $(cell).html(percentage + '%');
                });
            },
            initComplete: function() {
                var searchInput = $('#customer-group-ledger-table_filter input[type="search"]');
                searchInput.attr('placeholder', 'Search by name');
            }
        });
    });
</script> --}}
@endpush
@section("script")
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>

<script>
    $(document).ready(function() {

        new DataTable('#item-group-ledger-datatable', {
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: '{{ route('reports.ItemGroupLedger.get-data', $itemGroupLedgerId) }}',
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                // {data: 'id', name: 'id'},
                {data: 'name', name: 'name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.VoucherHead", ":guid") }}';
                        url = url.replace(':guid', row.guid);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                {data: 'total_sales', name: 'total_sales'},
                {data: 'qty_sold', name: 'qty_sold', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'avg_sales', name: 'avg_sales', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var SaleToTotal = 1;
                var QtySoldToTotal = 2;
                var AvgSaleToTotal = 3;


                var Saletotal = api.column(SaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var QtySoldtotal = api.column(QtySoldToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var AvgSaletotal = api.column(AvgSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                // var AvgSalePrice = Saletotal / data.count;
                var totalRecords = api.data().length;
                console.log(totalRecords);
                // var count = data.length();
                // console.log( 'count', count);

                var AvgSalePrice = totalRecords > 0 ? Saletotal / totalRecords : 0;

                $(api.column(SaleToTotal).footer()).html(number_format(Math.abs(Saletotal), 2));
                $(api.column(QtySoldToTotal).footer()).html(number_format(Math.abs(QtySoldtotal), 2));
                $(api.column(AvgSaleToTotal).footer()).html(number_format(Math.abs(AvgSalePrice), 2));
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