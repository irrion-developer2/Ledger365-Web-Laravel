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
                        <li class="breadcrumb-item active" aria-current="page">Sales by Items</li>
                    </ol>
                </nav>
            </div>
        </div>
            
                <div class="card">
                    <div class="card-body">
                        <div class="d-lg-flex align-items-center gap-2">
                            <h4 class="my-1 text-info">{{ $itemGroupLedger->item_group_name }} </h4>
                        </div>

                        <div class="table-responsive table-responsive-scroll border-0">
                            <table class="stripe row-border order-column" id="item-group-ledger-datatable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Total Sales</th>
                                        <th>Qty Sold</th>
                                        <th>
                                            ₹ Avg Sales
                                            <br>
                                            <span style="font-size: smaller;color: gray;">Price</span>
                                        </th>
                                        <th>
                                            Customer
                                            <br>
                                            <span style="font-size: smaller;color: gray;">Count</span>
                                        </th>
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
</div>
@endsection
@section("script")
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {

        new DataTable('#item-group-ledger-datatable', {
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
                url: '{{ route('reports.ItemGroupLedger.get-data', $itemGroupLedgerId) }}',
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {data: 'item_name', name: 'item_name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.ItemLedger", ":guid") }}';
                        url = url.replace(':guid', row.item_id);
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
                {data: 'customer_count', name: 'customer_count', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var SaleToTotal = 1;
                var QtySoldToTotal = 2;
                var AvgSaleToTotal = 3;
                var CountToTotal = 4;


                var Saletotal = api.column(SaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var QtySoldtotal = api.column(QtySoldToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var AvgSaletotal = api.column(AvgSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var Counttotal = api.column(CountToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var totalRecords = api.data().length;
                console.log(totalRecords);

                var AvgSalePrice = totalRecords > 0 ? Saletotal / totalRecords : 0;

                $(api.column(SaleToTotal).footer()).html(jsIndianFormat(Math.abs(Saletotal), 2));
                $(api.column(QtySoldToTotal).footer()).html(jsIndianFormat(Math.abs(QtySoldtotal), 2));
                $(api.column(AvgSaleToTotal).footer()).html(jsIndianFormat(Math.abs(AvgSalePrice), 2));
                $(api.column(CountToTotal).footer()).html(jsIndianFormat(Math.abs(Counttotal), 2));
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