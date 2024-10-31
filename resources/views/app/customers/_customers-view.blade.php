@extends("layouts.main")
@section('title', __('Ledger View | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content pt-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Ledger View</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Ledger View</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->

        <div class="card">
            <div class="card-body">

                <div class="card radius-10 border-start border-0 border-4 border-info">
                    <div class="card-body p-2">

                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-6">
                                    <h4 class="my-1 text-info">{{ $ledger->ledger_name }}</h4>
                                </div>
                                {{-- <div class="col-lg-6 text-end">
                                    <p class="btn btn-outline-danger border-1"><i class='lni lni-warning'></i> Overdue</p>
                                </div> --}}
                            </div>
                        </div>

                        <div class="row p-2 pt-0">
                            <div class="col-lg-9" style="padding: 12px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <p class="mb-0 font-13">Total Invoices</p>
                                        <h6><h6 id="totalInvoices">0</h6></h6>
                                    </div>
                                    <div class="col-lg-3">
                                        <p class="mb-0 font-13">Opening Balance</p>
                                        <h6 id="openingBalance"></h6>
                                    </div>
                                    <div class="col-lg-3">
                                        <p class="mb-0 font-13">Total Debit</p>
                                        <h6 id="totalDebit"></h6>
                                    </div>
                                    <div class="col-lg-3">
                                        <p class="mb-0 font-13">Total Credit</p>
                                        <h6 id="totalCredit"></h6>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3" style="padding: 12px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-2">
                                            
                                        </div>
                                        <div class="col-lg-10">
                                            <p class="mb-0 font-13">Net Outstanding</p>
                                            <h6 id="outstanding"></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                    </div>
                </div>

                @include('app.customers._ledger-view', ['ledger' => $ledger])

            </div>
        </div>


    </div>
</div>
@endsection
@push('css')

@endpush
@push('javascript')
@endpush
@section("script")
<script src="{{ url('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ url('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>

<script>
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var startDate = urlParams.get('start_date');
        var endDate = urlParams.get('end_date');

        function formatDateForDisplay(date) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(date).toLocaleDateString(undefined, options);
        }

        var table = $('#voucherEntriesTable').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: {
                url: "{{ route("customers.vouchers", ["customer" => $ledger->ledger_guid]) }}",
                type: 'GET',
                data: function (d) {
                    d.start_date = startDate;
                    d.end_date = endDate;   
                }
            },
            order: [],
            columns: [
                { data: 'voucher_date', name: 'voucher_date',orderable: false },
                { data: 'ledger_name', name: 'ledger_name',orderable: false },
                { data: 'voucher_number', name: 'voucher_number', orderable: false,
                    render: function(data, type, row) {
                        return '<a href="{{ url('reports/VoucherItem') }}/' + row.tally_voucher_id + '">' + data + '</a>';
                    }
                },
                { data: 'voucher_type_name', name: 'voucher_type_name', orderable: false },
                { data: 'debit', name: 'debit', className: 'text-end', orderable: false },
                { data: 'credit', name: 'credit', className: 'text-end', orderable: false },
                { data: 'running_balance', name: 'running_balance', className: 'text-end', orderable: false },
            ],
            initComplete: function(settings, json) {
                $('#totalInvoices').text(json.recordsTotal);
                var defaultStartDate = json.first_voucher_date;
                var defaultEndDate = json.last_voucher_date;

                if (!startDate && !endDate) {
                    startDate = defaultStartDate;
                    endDate = defaultEndDate;
                    dateRangeInput._flatpickr.setDate([startDate, endDate], false); 
                }
            },
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Function to remove commas from amounts and parse as float
                function parseAmount(value) {
                    if (typeof value === 'string') {
                        return parseFloat(value.replace(/,/g, '')) || 0;
                    }
                    return parseFloat(value) || 0;
                }

                // Calculate total debit
                var totalDebit = api.column(4).data().reduce(function(a, b) {
                    a = parseAmount(a);
                    b = parseAmount(b);
                    return a + b;
                }, 0);

                // Calculate total credit
                var totalCredit = api.column(5).data().reduce(function(a, b) {
                    a = parseAmount(a);
                    b = parseAmount(b);
                    return a + b;
                }, 0);

                // Calculate total running balance
                var totalRunningBalance = 0;
                var runningBalance = 0;
                api.rows().every(function(rowIdx) {
                    var rowData = this.data();
                    var debit = parseAmount(rowData.debit);
                    var credit = parseAmount(rowData.credit);
                    runningBalance += credit - debit;
                });

                totalRunningBalance = runningBalance;

                // Update footer
                $(api.column(4).footer()).html(jsIndianFormat(totalDebit));
                $(api.column(5).footer()).html(jsIndianFormat(totalCredit));
                $('#totalDebit').text(jsIndianFormat(totalDebit));
                $('#totalCredit').text(jsIndianFormat(totalCredit));

                // Handle opening balance
                var openingBalance = 0;
                if (data.length > 0 && data[0].opening_balance) {
                    openingBalance = parseAmount(data[0].opening_balance);
                }

                var firstRowRunningBalance = data.length > 0 && data[0].running_balance
                    ? parseAmount(data[0].running_balance)
                    : 0;

                var firstRowAmount = data.length > 0 && data[0].amount
                    ? parseAmount(data[0].amount)
                    : 0;

                var OeningB = firstRowRunningBalance - firstRowAmount;


                var lastRowRunningBalance = data.length > 0 && data[data.length - 1].running_balance
                    ? parseAmount(data[data.length - 1].running_balance)
                    : 0;

                // Update running balance and opening balance
                $('#totalRunningBalance').text(totalRunningBalance.toFixed(2));
                $('#openingBalance').text(jsIndianFormat(OeningB));
                $('#outstanding').text(jsIndianFormat(lastRowRunningBalance));
                $('#outstandingBalance').text(totalRunningBalance.toFixed(2));

                // Show/hide the button based on running balance
                if (totalRunningBalance > 0) {
                    $('.btn-outline-danger').show();
                } else {
                    $('.btn-outline-danger').hide();
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

        if (startDate && endDate) {
            dateRangeInput._flatpickr.setDate([startDate, endDate], false);
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
