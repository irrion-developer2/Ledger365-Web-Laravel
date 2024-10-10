@extends("layouts.main")
@section('title', __('Customers | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
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
                        <div class="col-lg-3">
                            <form id="dateRangeForm">
                                {{-- <label for="date_range" class="form-label">Date Range</label> --}}
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
                                    <option value="this_month" {{ request('custom_date_range') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                    <option value="last_month" {{ request('custom_date_range') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="this_quarter" {{ request('custom_date_range') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                                    <option value="prev_quarter" {{ request('custom_date_range') === 'prev_quarter' ? 'selected' : '' }}>Prev Quarter</option>
                                    <option value="this_year" {{ request('custom_date_range') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                    <option value="prev_year" {{ request('custom_date_range') === 'prev_year' ? 'selected' : '' }}>Prev Year</option>
                                </select>
                            </form>
                        </div>
                        {{-- <button id="filter-outstanding" class="btn btn-outline-secondary p-1">Outstanding</button>

                        <button id="filter-ageing" class="btn btn-outline-secondary p-1">Overdue</button>

                        <button id="filter-collection" class="btn btn-outline-secondary p-1">Collections</button>

                        <button id="filter-sale" class="btn btn-outline-secondary p-1">No Sales</button> --}}
                    </div>

                    <div class="table-responsive table-responsive-scroll border-0">

                        <table id="customer-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>Sales</th>
                                    <th>outstanding</th>
                                    <th>
                                        ₹ Pmt Collection
                                        <br>
                                        <span style="font-size: smaller;color: gray;">FY</span>
                                    </th>
                                    {{-- <th>
                                        Last Payment
                                    </th>
                                    <th>₹ Credit Limit</th>
                                    <th>₹ Credit Period</th> --}}
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
                                    {{-- <th></th>
                                    <th></th>
                                    <th></th> --}}
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
        var urlParams = new URLSearchParams(window.location.search);
        var startDate = urlParams.get('start_date');
        var endDate = urlParams.get('end_date');

        var customDateRange = urlParams.get('custom_date_range');

        if (customDateRange) {
            $('#custom_date_range').val(customDateRange);
        }

        var table = new DataTable('#customer-datatable', {
            fixedColumns: { start: 1, },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('customers.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.custom_date_range = customDateRange;
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
                {data: 'sales', name: 'sales'},
                {data: 'outstanding', name: 'outstanding', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'payment_collection', name: 'payment_collection', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                // {data: 'payment_date', name: 'payment_date', render: function(data, type, row) {
                //     return data ? data : '-';
                // }},
                // {data: 'credit_limit', name: 'credit_limit', render: function(data, type, row) {
                //     return data ? data : '-';
                // }},
                // {data: 'bill_credit_period', name: 'bill_credit_period', render: function(data, type, row) {
                //     return data ? data : '-';
                // }},

            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var LastSaleToTotal = 2;
                var OutstandingToTotal = 3;
                var PmtToTotal = 4;

                var LastSaletotal = api.column(LastSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Outstandingtotal = api.column(OutstandingToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Pmttotal = api.column(PmtToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                $(api.column(LastSaleToTotal).footer()).html(number_format(Math.abs(LastSaletotal), 2));
                $(api.column(OutstandingToTotal).footer()).html(number_format(Math.abs(Outstandingtotal), 2));
                $(api.column(PmtToTotal).footer()).html(number_format(Math.abs(Pmttotal), 2));
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

        function number_format(number, decimals) {
            if (isNaN(number)) return 0;
            number = parseFloat(number).toFixed(decimals);
            var parts = number.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        $('#resetDateRange').on('click', function() {
            $('.date-range').val('');
            
            // Remove the start_date and end_date from URL
            let url = new URL(window.location.href);
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');

            window.location.href = url.toString();
        });

    });
</script>
@endsection
