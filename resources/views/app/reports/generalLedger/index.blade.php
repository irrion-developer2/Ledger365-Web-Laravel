@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
    <link href="{{ url('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
            <div class="page-content pt-2">
                        <!--breadcrumb-->
                        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                            <div class="breadcrumb-title pe-3">Reports</div>
                            <div class="ps-3">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0 p-0">
                                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">General Ledger</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <!--end breadcrumb-->

                        <div class="card">
                            <div class="card-body">
                                <div class="d-lg-flex align-items-center mb-4 gap-3">
                                </div>
                                <div class="table-responsive table-responsive-scroll border-0">
                                    <table id="general-ledger-datatable" class="stripe row-border order-column" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Ledger group Name</th>
                                                <th>primary group</th>
                                                <th>Opening Balance	</th>
                                                <th>Debit</th>
                                                <th>Credit</th>
                                                <th>Closing Balance</th>
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://unpkg.com/vue2-datepicker@3.10.2/index.min.js"></script>
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>

<script>
    Vue.component('date-picker', window.DatePicker.default || window.DatePicker);

    new Vue({
        el: '#vue-datepicker-app',
        data: {
            dateRange: [],
            customDateRange: "{{ request('custom_date_range') }}",
            tableInitialized: false,
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
                this.dateRange = [];
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
                    $('#customer-datatable').DataTable().ajax.reload(null, false);
                }
            }
        },
        watch: {
            dateRange(newRange) {
                if (newRange.length === 2) {
                    this.updateURL();
                    this.reloadTableData();
                }
            }
        },
        mounted() {
            const urlParams = new URLSearchParams(window.location.search);
            const startDate = urlParams.get('start_date');
            const endDate = urlParams.get('end_date');
            if (startDate && endDate) {
                this.dateRange = [startDate, endDate];
            }
            $('#customer-datatable').on('init.dt', () => {
                this.tableInitialized = true;
            });
        }
    });


    $(document).ready(function() {
        const dataTable = $('#customer-datatable').DataTable({
            fixedColumns: { start: 1 },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('customers.get-data') }}",
                type: 'GET',
                data: function (d) {
                    const vueInstance = document.getElementById('vue-datepicker-app').__vue__;
                    if (vueInstance.dateRange.length === 2) {
                        d.start_date = vueInstance.dateRange[0];
                        d.end_date = vueInstance.dateRange[1];
                    }
                    d.custom_date_range = vueInstance.customDateRange || "all";
                }
            },
            columns: [
                {data: 'ledger_name', name: 'ledger_name',
                    render: function(data, type, row) {
                        let url = '{{ route("customers.show", ":guid") }}';
                        url = url.replace(':guid', row.ledger_guid);
                        return `<a href="${url}" style="color: #337ab7;">${data}</a>`;
                    }
                },
                {data: 'party_gst_in', name: 'party_gst_in', render: data => data || '-'},
                {data: 'sales', name: 'sales', render: data => data || '-'},
                {data: 'outstanding', name: 'outstanding', render: data => data || '-'},
                {data: 'payment_collection', name: 'payment_collection', render: data => data || '-'},
            ],
            footerCallback: function (row, data, start, end, display) {
                const api = this.api();
                const columnIndexes = { sales: 2, outstanding: 3, payment: 4 };
                
                const totals = {
                    sales: api.column(columnIndexes.sales).data().reduce((a, b) => (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0), 0),
                    outstanding: api.column(columnIndexes.outstanding).data().reduce((a, b) => (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0), 0),
                    payment: api.column(columnIndexes.payment).data().reduce((a, b) => (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0), 0),
                };

                $(api.column(columnIndexes.sales).footer()).html(jsIndianFormat(Math.abs(totals.sales)));
                $(api.column(columnIndexes.outstanding).footer()).html(jsIndianFormat(Math.abs(totals.outstanding)));
                $(api.column(columnIndexes.payment).footer()).html(jsIndianFormat(Math.abs(totals.payment)));
            },
            search: {
                orthogonal: { search: 'plain' }
            }
        });

        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }
    });
</script>
@endsection
