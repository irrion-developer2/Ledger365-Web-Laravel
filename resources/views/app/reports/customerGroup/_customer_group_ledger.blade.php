@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")

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
                        <li class="breadcrumb-item active" aria-current="page">Sales by Customers</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


                <div class="card">
                    <div class="card-body">
                        <div class="d-lg-flex align-items-center gap-2">
                            <h4 class="my-1 text-info">{{ $customerGroupLedger->ledger_group_name }} </h4>
                        </div>

                        <div class="table-responsive table-responsive-scroll border-0">
                    
                            <table class="stripe row-border order-column" id="customer-group-ledger-datatable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Total Sales</th>
                                        <th>Transactions Count</th>
                                        <th>Avg Sales</th>
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
                                    </tr>
                                </tfoot>
                            </table>


                        </div>

                    </div>
                </div>
        
        </div>
        <!--end email wrapper-->
    </div>
</div>
@endsection
@section('script')
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {

        new DataTable('#customer-group-ledger-datatable', {
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
                url: "{{ route('reports.CustomerGroupLedger.data', $customerGroupLedgerId) }}",
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {
                    data: 'ledger_name',
                    name: 'ledger_name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.VoucherHead", ":guid") }}';
                        url = url.replace(':guid', row.ledger_guid);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                { data: 'total_sales', name: 'total_sales', className: 'text-end' },
                {{--  { data: 'percentage', name: 'percentage', className: 'text-end' },    --}}
                { data: 'total_count', name: 'total_count', className: 'text-end' },
                { data: 'avg_sales', name: 'avg_sales', className: 'text-end' },
                // { data: 'sales_count', name: 'sales_count', className: 'text-end' }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var SaleToTotal = 1; 
                var QtySoldToTotal = 2; 
                var AvgSaleToTotal = 3; 
            
                var Saletotal = api.column(SaleToTotal, { page: 'current' }).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
            
                var QtySoldtotal = api.column(QtySoldToTotal, { page: 'current' }).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var AvgSales = api.column(AvgSaleToTotal, { page: 'current' }).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
            
                {{--  var AvgSales = QtySoldtotal > 0 ? Saletotal / QtySoldtotal : 0;  --}}
            
                $(api.column(SaleToTotal).footer()).html(jsIndianFormat(Math.abs(Saletotal), 2));
                $(api.column(QtySoldToTotal).footer()).html(jsIndianFormat(Math.abs(QtySoldtotal), 0)); 
                $(api.column(AvgSaleToTotal).footer()).html(jsIndianFormat(Math.abs(AvgSales), 2)); 
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
