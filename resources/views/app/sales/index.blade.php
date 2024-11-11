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


                    <div class="d-lg-flex align-items-center gap-2">
                        <div class="col-lg-3">
                            <form id="dateRangeForm">
                                <div class="input-group">
                                    <input type="text" id="date_range" name="date_range" class="form-control date-range" placeholder="Select Date Range">
                                        <button type="button" id="resetDateRange" class="btn btn-outline-secondary">
                                            <i class="fadeIn animated bx bx-refresh" aria-hidden="true"></i> 
                                        </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-2">
                            <form id="customDateForm">
                                <select id="custom_date_range" name="custom_date_range" class="form-select">
                                    <option value="all" {{ request('custom_date_range') === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="this_month" {{ request('custom_date_range') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                    <option value="last_month" {{ request('custom_date_range') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="this_quarter" {{ request('custom_date_range') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                                    <option value="prev_quarter" {{ request('custom_date_range') === 'prev_quarter' ? 'selected' : '' }}>Prev Quarter</option>
                                    <option value="this_year" {{ request('custom_date_range') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                    <option value="prev_year" {{ request('custom_date_range') === 'prev_year' ? 'selected' : '' }}>Prev Year</option>
                                </select>
                            </form>
                        </div>
                        <div class="col-lg-7 text-end">
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
                                    <th>Total</th>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var startDate = urlParams.get('start_date');
        var endDate = urlParams.get('end_date');

        var customDateRange = urlParams.get('custom_date_range');

        if (customDateRange) {
            $('#custom_date_range').val(customDateRange);
        }

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
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.custom_date_range = customDateRange;
                }
            },
            columns: [
                {data: 'ledger_name', name: 'ledger_name'},
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

                $(api.column(InvoiceAmountToTotal).footer()).html(jsIndianFormat(Math.abs(InvoiceAmounttotal)));
            },
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        const dateRangeInput = document.querySelector(".date-range");
        flatpickr(dateRangeInput, {
            mode: "range",
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    let startDate = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                    let endDate = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                    let url = new URL(window.location.href);
                    url.searchParams.set('start_date', startDate);
                    url.searchParams.set('end_date', endDate);
                    window.location.href = url.toString();
                }
            }
        });

        // Prepopulate date range input if already set
        if (startDate && endDate) {
            dateRangeInput._flatpickr.setDate([startDate, endDate], false);
        }

        $('#custom_date_range').on('change', function() {
            var selectedRange = $(this).val();
            var url = new URL(window.location.href);
            url.searchParams.set('custom_date_range', selectedRange);
            window.location.href = url.toString();
        });

        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

        $('#resetDateRange').on('click', function() {
            $('.date-range').val('');
            let url = new URL(window.location.href);
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');

            window.location.href = url.toString();
        });

    });
</script>
@endsection
