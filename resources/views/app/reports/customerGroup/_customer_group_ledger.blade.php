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


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;"  onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> Sales by Customers Group</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.CustomerGroupLedger', ['CustomerGroupLedger' => $item->ledger_group_id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('CustomerGroupLedger') == $item->ledger_group_id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                    <span>{{ $item->ledger_group_name }}</span>
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
                        <h4 class="my-1 text-info">{{ $customerGroupLedger->ledger_group_name }} </h4>
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="stripe row-border order-column" id="customer-group-ledger-datatable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Total Sales</th>
                                        <th>% of Total Sales</th>
                                        <th>Transactions Count</th>
                                        <th>Avg Sales</th>
                                        {{-- <td>Sales count</td> --}}
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
                                        {{-- <td>Sales count</td> --}}
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
@section('script')
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {

        new DataTable('#customer-group-ledger-datatable', {
            fixedColumns: {
                start: 1,
            },
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
                { data: 'percentage', name: 'percentage', className: 'text-end' },  
                { data: 'total_count', name: 'total_count', className: 'text-end' },
                { data: 'avg_sales', name: 'avg_sales', className: 'text-end' },
                // { data: 'sales_count', name: 'sales_count', className: 'text-end' }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var SaleToTotal = 1; 
                var QtySoldToTotal = 3; 
                var AvgSaleToTotal = 4; 
            
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
            
            {{--  footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var SaleToTotal = 1;
                var QtySoldToTotal = 3;
                var AvgSaleToTotal = 4;

                var totalPercentage = api.column(2, { page: 'current' }).data().reduce(function(a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0);

                var Saletotal = api.column(SaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var QtySoldtotal = api.column(QtySoldToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var AvgSaletotal = api.column(AvgSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);


                var totalSalesCount = data.reduce(function(total, item) {
                    return total + (parseInt(item.sales_count, 10) || 0);
                }, 0);

                var AvgSales = totalSalesCount > 0 ? Saletotal / totalSalesCount : 0;

                $(api.column(SaleToTotal).footer()).html(jsIndianFormat(Math.abs(Saletotal), 2));
                $(api.column(QtySoldToTotal).footer()).html(jsIndianFormat(Math.abs(QtySoldtotal), 2));
                $(api.column(2).footer()).html(Math.abs(totalPercentage / data.length).toFixed(2) + '%'); // Average percentage
                $(api.column(AvgSaleToTotal).footer()).html(jsIndianFormat(Math.abs(AvgSales), 2));
            },  --}}
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
