@extends("layouts.main")
@section('title', __('Employees | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Employees</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Employees</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                            <a class="btn btn-primary" href="{{ route('employees.add') }}"><i class="bx bx-plus fs-5"></i><span>Employee</span></a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-lg-flex align-items-center gap-2">
                        {{-- <div class="col-lg-3">
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
                                    <option value="this_month" {{ request('custom_date_range') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                    <option value="last_month" {{ request('custom_date_range') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="this_quarter" {{ request('custom_date_range') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                                    <option value="prev_quarter" {{ request('custom_date_range') === 'prev_quarter' ? 'selected' : '' }}>Prev Quarter</option>
                                    <option value="this_year" {{ request('custom_date_range') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                    <option value="prev_year" {{ request('custom_date_range') === 'prev_year' ? 'selected' : '' }}>Prev Year</option>
                                </select>
                            </form>
                        </div> --}}
                    </div>

                    <div class="table-responsive table-responsive-scroll border-0">

                        <table id="employees-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Email</th>
                                    <th>Tally Connector Id</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
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

        var startDate = urlParams.get('start_date');
        var endDate = urlParams.get('end_date');

        var customDateRange = urlParams.get('custom_date_range');

        if (customDateRange) {
            $('#custom_date_range').val(customDateRange);
        }

        new DataTable('#employees-datatable', {
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('employees.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.custom_date_range = customDateRange;
                }
            },
            columns: [
                {data: 'name', name: 'name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'email', name: 'email', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'tally_connector_id', name: 'tally_connector_id', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'role', name: 'role', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'status', name: 'status', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'created_at', name: 'created_at', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'updated_at', name: 'updated_at', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
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
<script>
    function changeStatus(userId, status) {
        $.ajax({
            url: '/update-user-status', // Replace with your route
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                user_id: userId,
                status: status
            },
            success: function(response) {
                if(response.success) {
                    $('#user-table').DataTable().ajax.reload(); // Reload table data
                } else {
                    alert('Failed to update status.');
                }
            },
            error: function() {
                alert('An error occurred.');
            }
        });
    }
</script>
@endsection
