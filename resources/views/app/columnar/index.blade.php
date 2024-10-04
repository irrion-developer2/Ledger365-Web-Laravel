@extends("layouts.main")
@section('title', __('Columnar | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Columnar</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Columnar</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-lg-flex align-items-center gap-3">
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
                        {{-- <div class="col-lg-2">
                            <form id="voucherTypeForm">
                                <label for="voucher_type" class="form-label">Transaction Type</label>
                                <select id="voucher_type" name="voucher_type" class="form-select">
                                    <option value="">All</option>
                                    <option value="Sales">Sale</option>
                                    <option value="Purchase">Purchase</option>
                                    <option value="Credit Note">CreditNote</option>
                                    <option value="Debit Note">DebitNote</option>
                                    <option value="Receipt">Receipt</option>
                                    <option value="Payment">Payment</option>
                                </select>
                            </form>
                        </div> --}}
                    </div>

                    <div class="table-responsive table-responsive-scroll border-0">
                        <table id="SalesColumnar-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Voucher No.</th>
                                    <th>Voucher Type</th>
                                    <th>Buyer Name</th>
                                    <th>Buyer Addr</th>
                                    <th>State</th>
                                    <th>Country</th>
                                    <th>GSTIN</th>
                                    <th>Registration Type</th>
                                    <th>Place Of Supply</th>
                                    <th>Gross Total</th>
                                    <th>Taxable Value</th>
                                    <th>IGST</th>
                                    <th>SGST</th>
                                    <th>CGST</th>
                                    <th>Round Off</th>
                                    <th>Product</th>
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
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
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

        var table = $('#SalesColumnar-datatable').DataTable({
            fixedColumns: {
                start: 2,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            orderCellsTop: true,
            ajax: {
                url: "{{ route('columnar.get-data') }}",
                type: 'GET',
                data: function (d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.custom_date_range = customDateRange;
                    // d.voucher_type = $('#voucher_type').val();
                }
            },
            columns: [
                { data: 'voucher_date', name: 'voucher_date', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'party_ledger_name', name: 'party_ledger_name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'voucher_number', name: 'voucher_number' ,
                    render: function(data, type, row) {
                        var url = '{{ route("sales.items", ":id") }}';
                        url = url.replace(':id', row.id);
                        return '<a href="' + url + '" style="color: #337ab7;">' + data + '</a>';
                    }
                },
                { data: 'voucher_type', name: 'voucher_type' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'buyer_name', name: 'buyer_name' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'buyer_addr', name: 'buyer_addr' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'state', name: 'state' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'country', name: 'country' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'buyer_gstin', name: 'buyer_gstin' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'gst_registration_type', name: 'gst_registration_type' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'place_of_supply', name: 'place_of_supply' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'gross_total', name: 'gross_total' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'taxable_value', name: 'taxable_value' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'igst', name: 'igst' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'sgst', name: 'sgst' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'cgst', name: 'cgst' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                { data: 'round_off', name: 'round_off' , render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {
                    name: 'Product View',
                    render: function(data, type, row) {
                        var url = '{{ route("sales.items", ":id") }}';
                        url = url.replace(':id', row.id);

                        return '<button class="btn btn-primary btn-sm" onclick="window.location.href=\'' + url + '\'">Product View</button>';
                    }
                },
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 :typeof i === 'number' ?i : 0;};

                var totalGrossTotal = api.column(11).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var totalTaxableValue = api.column(12).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var totalIGST = api.column(13).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var totalSGST = api.column(14).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var totalCGST = api.column(15).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var totalRoundOff = api.column(16).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);


                $(api.column(11).footer()).html(totalGrossTotal.toFixed(3));
                $(api.column(12).footer()).html(totalTaxableValue.toFixed(3));
                $(api.column(13).footer()).html(totalIGST.toFixed(3));
                $(api.column(14).footer()).html(totalSGST.toFixed(3));
                $(api.column(15).footer()).html(totalCGST.toFixed(3));
                $(api.column(16).footer()).html(Math.abs(totalRoundOff).toFixed(3));
            },
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

         

        const dateRangeInput = document.querySelector(".date-range");
        flatpickr(".date-range", {
            mode: "range",
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            defaultDate: [new Date(new Date().setDate(new Date().getDate() - 30)), new Date()],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    let startDate = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                    let endDate = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                    let url = new URL(window.location.href);
                    url.searchParams.set('start_date', startDate);
                    url.searchParams.set('end_date', endDate);
                    window.location.href = url.toString();
                    // table.ajax.reload(); // Refresh the table data
                }
            }
        });

        if (startDate && endDate) {
            dateRangeInput._flatpickr.setDate([startDate, endDate], false);
        }

        $('#custom_date_range').on('change', function() {
            var selectedRange = $(this).val();
            var url = new URL(window.location.href);
            url.searchParams.set('custom_date_range', selectedRange);
            window.location.href = url.toString();
        });

        
        $('#resetDateRange').on('click', function() {
            $('.date-range').val('');
            let url = new URL(window.location.href);
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');

            window.location.href = url.toString();
        });

        const voucherTypeSelect = document.getElementById('voucher_type');
        voucherTypeSelect.addEventListener('change', function() {
            let voucherType = this.value;
            let url = new URL(window.location.href);
            url.searchParams.set('voucher_type', voucherType);
            window.location.href = url.toString();
        });


        $('#voucher_type').on('change', function() {
            table.ajax.reload(); // Reload the table data when voucher type changes
        });

        // Show/Hide Columns
        $('input.toggle-vis').on('change', function (e) {
            var column = table.column($(this).attr('data-column'));
            column.visible(!column.visible());
        });


    });
</script>
@endsection
