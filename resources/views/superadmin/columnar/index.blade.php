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
                                <input type="text" id="date_range" name="date_range" class="form-control date-range" placeholder="Select Date Range">
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
        // Initialize DataTable
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
                    var urlParams = new URLSearchParams(window.location.search);
                    d.start_date = urlParams.get('start_date') || $('#date_range').data('start');
                    d.end_date = urlParams.get('end_date') || $('#date_range').data('end');
                    d.voucher_type = $('#voucher_type').val();
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
        });

        function updateUrlParameter(key, value) {
            var url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url);
        }

        function getInitialDates() {
            var urlParams = new URLSearchParams(window.location.search);
            var startDate = urlParams.get('start_date');
            var endDate = urlParams.get('end_date');

            if (startDate && endDate) {
                return [startDate, endDate];
            }

            return [new Date(new Date().setDate(new Date().getDate() - 30)), new Date()];
        }

        flatpickr(".date-range", {
            mode: "range",
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            defaultDate: getInitialDates(),
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    let startDate = selectedDates[0].toISOString().split('T')[0];
                    let endDate = selectedDates[1].toISOString().split('T')[0];

                    $('#date_range').data('start', startDate);
                    $('#date_range').data('end', endDate);

                    updateUrlParameter('start_date', startDate);
                    updateUrlParameter('end_date', endDate);

                    table.ajax.reload();
                }
            }
        });

        var initialDates = getInitialDates();
        if (initialDates[0] && initialDates[1]) {
            $('#date_range').data('start', initialDates[0]);
            $('#date_range').data('end', initialDates[1]);
        }

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
