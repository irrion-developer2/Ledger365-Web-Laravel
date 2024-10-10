@extends("layouts.main")
@section('title', __('Suppliers | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Suppliers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Suppliers</li>
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

                        <button id="filter-payment" class="btn btn-outline-secondary p-1">Payment</button> --}}
                    </div>

                    <div class="table-responsive table-responsive-scroll border-0">

                        <table id="supplier-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Ledger Name</th>
                                    <th>GSTIN</th>
                                    <th>
                                        Purchase
                                        {{-- <br>
                                        <span style="font-size: smaller;color: gray;">(Last 30 days)</span> --}}
                                    </th>
                                    <th>
                                        Returns
                                        {{-- <br>
                                        <span style="font-size: smaller;color: gray;">(Last 30 days)</span> --}}
                                    </th>
                                    <th>Net ₹ Due</th>
                                    <th>₹ Overdue</th>
                                    <th>
                                        Overdue<br>
                                        <span style="font-size: smaller;color: gray;">Since</span>
                                    </th>
                                    <th>
                                        ₹ On Account<br>
                                        <span style="font-size: smaller;color: gray;">As of Today</span>
                                    </th>
                                    <th>
                                        ₹ Pmt Made
                                        <br>
                                        <span style="font-size: smaller;color: gray;">FY</span>
                                    </th>
                                    <th>
                                        Last Payment
                                    </th>
                                    <th>₹ Credit Limit</th>
                                    <th>₹ Credit Period</th>
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
                                    <th></th>
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
<script>
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var filterOutstanding = urlParams.get('filter_outstanding') === 'true';
        var filterAgeing = urlParams.get('filter_ageing') === 'true';
        var filterPayment = urlParams.get('filter_payment') === 'true';

        var startDate = urlParams.get('start_date');
        var endDate = urlParams.get('end_date');

        var customDateRange = urlParams.get('custom_date_range');

        if (customDateRange) {
            $('#custom_date_range').val(customDateRange);
        }

        new DataTable('#supplier-datatable', {
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('suppliers.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.filter_outstanding = filterOutstanding;
                    d.filter_ageing = filterAgeing;
                    d.filter_payment = filterPayment;
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.custom_date_range = customDateRange;
                }
            },
            columns: [
                // {data: 'id', name: 'id'},
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
                {data: 'purchase_last_30_days', name: 'purchase_last_30_days',
                    searchPanes: {
                        orthogonal: 'plain'
                    }
                },
                {data: 'return30', name: 'return30',
                    searchPanes: {
                        orthogonal: 'plain'
                    }
                },
                {data: 'outstanding', name: 'outstanding', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'overdue', name: 'overdue', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'overdue_date', name: 'overdue_date', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'opening_balance', name: 'opening_balance', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'payment_collection', name: 'payment_collection', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'payment_date', name: 'payment_date', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'credit_limit', name: 'credit_limit', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'bill_credit_period', name: 'bill_credit_period', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var LastSaleToTotal = 2;
                var ReturnToTotal = 3;
                var OutstandingToTotal = 4;
                var OverdueToTotal = 5;
                var PmtToTotal = 8;


                var LastSaletotal = api.column(LastSaleToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var Returntotal = api.column(ReturnToTotal).data().reduce(function (a, b) {
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
                $(api.column(ReturnToTotal).footer()).html(number_format(Math.abs(Returntotal), 2));
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

        $('#filter-payment').on('click', function () {
            filterPayment = !filterPayment;
            var newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_payment', filterPayment ? 'true' : 'false');
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
