@extends("layouts.main")
@section('title', __('Ledger View | PreciseCA'))
@section("style")
    <link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link href="https://unpkg.com/vue2-datepicker@3.10.2/index.css" rel="stylesheet">
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
                            </div>
                        </div>

                        <div class="row p-2 pt-0">
                            <div class="col-lg-9" style="padding: 12px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <p class="mb-0 font-13">Total Invoices</p>
                                        <h6 id="totalInvoices">0</h6>
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://unpkg.com/vue2-datepicker@3.10.2/index.min.js"></script>
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>

<script>
    $(document).ready(function() {
        Vue.component('date-picker', window.DatePicker.default || window.DatePicker);

        new Vue({
            el: '#vue-datepicker-app',
            data: {
                dateRange: [],
                customDateRange: "{{ request('custom_date_range') }}",
                tableInitialized: false,
                firstVoucherDate: null,
                lastVoucherDate: null,
                totalInvoices: 0,
                customDateRangeOptions: [
                    {
                        label: "General",
                        options: [
                            { text: "All", value: "all" }
                        ]
                    },
                    {
                        label: "Monthly",
                        options: [
                            { text: "This Month", value: "this_month" },
                            { text: "Last Month", value: "last_month" }
                        ]
                    },
                    {
                        label: "Quarterly",
                        options: [
                            { text: "This Quarter", value: "this_quarter" },
                            { text: "Prev Quarter", value: "prev_quarter" }
                        ]
                    },
                    {
                        label: "Yearly",
                        options: [
                            { text: "This Year", value: "this_year" },
                            { text: "Prev Year", value: "prev_year" }
                        ]
                    }
                ]
            },
            methods: {
                resetDateRange() {
                    this.dateRange = [this.firstVoucherDate, this.lastVoucherDate];
                    this.updateURL();
                    this.reloadTableData();
                },
                updateCustomRange(event) {
                    this.customDateRange = event.target.value;
                    this.updateURL();
                    this.reloadTableData();
                },
                updateURL() {
                    const url = new URL(window.location.href);
                    if (this.dateRange.length === 2) {
                        url.searchParams.set('start_date', this.dateRange[0]);
                        url.searchParams.set('end_date', this.dateRange[1]);
                    } else {
                        url.searchParams.delete('start_date');
                        url.searchParams.delete('end_date');
                    }
                    url.searchParams.set('custom_date_range', this.customDateRange);
                    window.history.pushState({}, '', url.toString());
                },
                reloadTableData() {
                    if (this.tableInitialized) {
                        $('#voucherEntriesTable').DataTable().ajax.reload(null, false);
                    }
                },
                fetchTotalInvoices() {
                    if (this.tableInitialized) {
                        $('#voucherEntriesTable').DataTable().ajax.reload(null, (json) => {
                            this.totalInvoices = json.total_invoices || 0;
                            $('#summaryTotalInvoices').text(jsIndianFormat(this.totalInvoices));
                            console.log("Total Invoices Updated:", this.totalInvoices);
                        }, false);
                    }
                },
                initializeDateRangeFromURL() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const start_date = urlParams.get('start_date');
                    const end_date = urlParams.get('end_date');
                    if (start_date && end_date) {
                        this.dateRange = [start_date, end_date];
                    }
                }
            },
            watch: {
                dateRange(newRange) {
                    if (newRange.length === 2) {
                        this.updateURL();
                        this.fetchTotalInvoices(); 
                    }
                }
            },
            mounted() {
                const vm = this;
                this.initializeDateRangeFromURL();

                $('#voucherEntriesTable').DataTable({
                    processing: true,
                    serverSide: true,
                    paging: false,
                    ajax: {
                        url: "{{ route("customers.vouchers", ["customer" => $ledger->ledger_guid]) }}",
                        type: 'GET',
                        data: function(d) {
                            const vueInstance = document.getElementById('vue-datepicker-app').__vue__;
                            if (vueInstance.dateRange.length === 2) {
                                d.start_date = vueInstance.dateRange[0];
                                d.end_date = vueInstance.dateRange[1];
                            }
                            d.custom_date_range = vueInstance.customDateRange || "all";
                        },
                        dataSrc: function(json) {
                            // Initialize date picker with date range from server
                            vm.firstVoucherDate = json.first_voucher_date;
                            vm.lastVoucherDate = json.last_voucher_date;
                            vm.totalInvoices = json.total_invoices;

                            $('#totalInvoices').text(vm.totalInvoices);
                            console.log("Initial Total Invoices:", vm.totalInvoices);
                            if (!vm.dateRange.length) {
                                vm.dateRange = [vm.firstVoucherDate, vm.lastVoucherDate];
                            }
                            return json.data;
                        }
                    },
                    order: [],
                    columns: [
                        { data: 'voucher_date', name: 'voucher_date', orderable: false },
                        { data: 'ledger_name', name: 'ledger_name', orderable: false },
                        { data: 'voucher_number', name: 'voucher_number', orderable: false, 
                            {{--  render: function(data, type, row) {
                                return '<a href="{{ url('reports/VoucherItem') }}/' + row.voucher_id + '">' + data + '</a>';
                            }  --}}
                        },
                        { data: 'voucher_type_name', name: 'voucher_type_name', orderable: false },
                        { data: 'debit', name: 'debit', className: 'text-end', orderable: false },
                        { data: 'credit', name: 'credit', className: 'text-end', orderable: false },
                        { data: 'running_balance', name: 'running_balance', className: 'text-end', orderable: false },
                    ],
                    footerCallback: function(row, data, start, end, display) {
                        var api = this.api();

                        function parseAmount(value) {
                            if (typeof value === 'string') {
                                return parseFloat(value.replace(/,/g, '')) || 0;
                            } else if (typeof value === 'number') {
                                return value;
                            } else {
                                return 0;
                            }
                        }

                        var totalDebit = api.column(4).data().reduce(function(a, b) {
                            return parseAmount(a) + parseAmount(b);
                        }, 0);

                        var totalCredit = api.column(5).data().reduce(function(a, b) {
                            return parseAmount(a) + parseAmount(b);
                        }, 0);

                        var totalRunningBalance = 0;
                        api.rows().every(function(rowIdx) {
                            var rowData = this.data();
                            totalRunningBalance += parseAmount(rowData.credit) - parseAmount(rowData.debit);
                        });

                        $(api.column(4).footer()).html(jsIndianFormat(totalDebit));
                        $(api.column(5).footer()).html(jsIndianFormat(totalCredit));
                        $('#totalDebit').text(jsIndianFormat(totalDebit));
                        $('#totalCredit').text(jsIndianFormat(totalCredit));

                        var firstRowRunningBalance = parseAmount(data[0]?.running_balance || '0');
                        var firstRowAmount = data.length > 0 && data[0].amount
                            ? parseAmount(data[0].amount)
                            : 0;
                            
                        var OeningB = firstRowRunningBalance - firstRowAmount;

                        var lastRowRunningBalance = parseAmount(data[data.length - 1]?.running_balance || '0');

                        $('#totalRunningBalance').text(totalRunningBalance.toFixed(2));
                        $('#openingBalance').text(jsIndianFormat(firstRowRunningBalance));
                        $('#outstanding').text(jsIndianFormat(lastRowRunningBalance));
                        $('#outstandingBalance').text(totalRunningBalance.toFixed(2));
                    }
                });

                this.tableInitialized = true;
            }
        });
    });
</script>

@endsection
