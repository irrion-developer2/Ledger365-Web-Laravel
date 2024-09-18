@extends("layouts.main")
@section('title', __('Invoices | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Invoices</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">


                    <div class="d-lg-flex align-items-center">
                        <div class="col-lg-3">
                            <form id="dateRangeForm">
                                <label for="date_range" class="form-label">Date Range</label>
                                <input type="text" id="date_range" name="date_range" class="form-control date-range" placeholder="Select Date Range">
                            </form>
                        </div>
                        <div class="col-lg-9 text-end">
                            <a href="{{ route('columnar.index') }}" class="btn btn-outline-primary border-1">
                                <span>Sales Columnar Report</span>
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive table-responsive-scroll border-0">
                        <table id="sales-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>Invoice Date</th>
                                    <th>Invoice Number</th>
                                    <th>Invoice Amount</th>
                                    <th>Place Of Supply</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>Invoice Date</th>
                                    <th>Invoice Number</th>
                                    <th>Invoice Amount</th>
                                    <th>Place Of Supply</th>
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function() {
        var table = $('#sales-datatable').DataTable({
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('sales.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.start_date = $('#date_range').data('start');
                    d.end_date = $('#date_range').data('end');
                }
            },
            columns: [
                {data: 'party_ledger_name', name: 'party_ledger_name'},
                {data: 'gst_in', name: 'gst_in', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'voucher_date', name: 'voucher_date', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'voucher_number', name: 'voucher_number',
                    render: function(data, type, row) {
                        var url = '{{ route("sales.items", ":id") }}';
                        url = url.replace(':id', row.id);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                {data: 'debit', name: 'debit', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'place_of_supply', name: 'place_of_supply', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var InvoiceAmountToTotal = 4;

                var InvoiceAmounttotal = api.column(InvoiceAmountToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                $(api.column(InvoiceAmountToTotal).footer()).html(number_format(Math.abs(InvoiceAmounttotal), 2));
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

        // Initialize date range picker
        flatpickr(".date-range", {
            mode: "range",
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            defaultDate: [new Date(new Date().setDate(new Date().getDate() - 30)), new Date()],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    let [startDate, endDate] = selectedDates.map(date => date.toISOString().split('T')[0]);
                    $('#date_range').data('start', startDate);
                    $('#date_range').data('end', endDate);
                    table.ajax.reload(); // Refresh the table data
                }
            }
        });
    });
</script>
@endsection
