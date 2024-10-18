@extends("layouts.main")
@section('title', __('Sales by Items | PreciseCA'))
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
    <div class="page-content p-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Stock Items</div>
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
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;"  onclick="history.back();" class="btn btn-primary compose-mail-btn"><i class='bx bx-left-arrow-alt me-2'></i> Sales by Item</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('SaleStockItem.items', ['SaleStockItem' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('SaleStockItem') == $item->id ? 'active' : '' }}" style="border-top: none;">
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
                        <h4 class="my-1 text-info">{{ $saleStockItem->name }}</h4>
                        {{-- <span>Sales by Item Details </span> --}}
                    </div>
                </div>
               
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                        <span class="px-4">Sales by Item Details </span>
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="table table-striped" id="sale-stock-table" width="100%">
                                <thead>
                                    <tr>
                                        <td>Name</td>
                                        <td>Voucher Number</td>
                                        <td>Total Sales</td>
                                        <td>Qty Sold</td>
                                        <td>Avg Sales (price)</td>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td></td>
                                        <td id="total-sale"></td>
                                        <td></td>
                                        <td id="avg-sale"></td>
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
    $('#sale-stock-table').DataTable({
        processing: true,
        serverSide: true,
        paging: false,
        ajax: '{{ route('stock-items.SaleStockItem.data', $saleStockItemId) }}',
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
            { data: 'voucher_number', name: 'voucher_number' },
            { data: 'amount', name: 'amount' }, 
            { 
                data: 'billed_qty', name: 'billed_qty',
                render: function(data, type, row) {
                    return data + ' ' + row.unit;  
                }
            },
            { data: 'rate', name: 'rate' },
        ],
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();

            
            var totalSale = api.column(2, { page: 'current' }).data().reduce(function(a, b) {
                return (parseFloat(a) || 0) + (parseFloat(b) || 0);
            }, 0);

            var totalWeightedSum = api.data().reduce(function(acc, row) {
                return acc + (parseFloat(row.billed_qty) || 0) * (parseFloat(row.rate) || 0);
            }, 0);

            // console.log('totalWeightedSum', totalWeightedSum);
            

        
            var totalQty = api.data().reduce(function(acc, row) {
                var billedQty = parseFloat(row.billed_qty) || 0;
                return billedQty > 0 ? acc + 1 : acc;
            }, 0);



            // console.log('totalQty', totalQty);

           
            var avgSale = totalQty ? totalWeightedSum / totalQty : 0;

            // console.log('avgSale', avgSale);

            // Update footer
            $(api.column(2).footer()).html(Math.abs(totalSale).toFixed(2));
            $(api.column(4).footer()).html(Math.abs(avgSale).toFixed(2));
        }

    });
});

</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>

@endsection