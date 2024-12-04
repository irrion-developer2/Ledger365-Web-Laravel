@extends("layouts.main")
@section('title', __('Stock Items | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Stock Items</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Stock Items</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-lg-flex align-items-center gap-3"></div>

                    <div class="table-responsive table-responsive-scroll border-0">

                        <table id="stockItem-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>HSN Code</th>
                                    <th>Closing Stock</th>
                                    <th>Avg pr Rate</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
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
<script>
    $(document).ready(function() {
        new DataTable('#stockItem-datatable', {
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
                url: "{{ route('StockItem.get-data') }}",
                type: 'GET',
            },
            columns: [
                {data: 'item_name', name: 'item_name',
                    {{--  render: function(data, type, row) {
                        var url = '{{ route("StockItem.items", ":id") }}';
                        url = url.replace(':id', row.id);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }  --}}
                },
                {data: 'hsn_code', name: 'hsn_code', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'closing_stock', name: 'closing_stock', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'opening_rate', name: 'opening_rate', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'overall_amount', name: 'overall_amount', className: 'text-end', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
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
