@extends("layouts.main")
@section('title', __('Ledger View | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
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
                                    <h4 class="my-1 text-info">{{ $ledger->language_name }}</h4>
                                </div>
                                <div class="col-lg-6 text-end">
                                    <p class="btn btn-outline-danger border-1"><i class='lni lni-warning'></i> Overdue</p>
                                </div>
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
                                            <p class="mb-0 font-13 btn btn border-0"><i class='bx bx-info-circle'></i></p>
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

                @include('superadmin.customers._ledger-view', ['ledger' => $ledger])

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
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>
<script src="{{ url('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ url('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            var table = $('#voucherEntriesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("customers.vouchers", ["customer" => $ledger->guid]) }}',
                columns: [
                    { data: 'voucher_date', name: 'voucher_date' },
                    // { data: 'ledger_name', name: 'ledger_name' },
                    { data: 'voucher_number', name: 'voucher_number',
                        render: function(data, type, row) {
                            return '<a href="{{ url('reports/VoucherItem') }}/' + row.tally_voucher_id + '">' + data + '</a>';
                        }
                    },
                    { data: 'voucher_type', name: 'voucher_type' },
                    { data: 'debit', name: 'debit', className: 'text-end' },
                    { data: 'credit', name: 'credit', className: 'text-end' },
                    { data: null, defaultContent: '', className: 'text-end' }
                ],
                initComplete: function(settings, json) {
                    $('#totalInvoices').text(json.recordsTotal);
                },
                drawCallback: function(settings) {
                    $('#totalInvoices').text(settings.json.recordsTotal);

                    var api = this.api();

                    var totalDebit = 0;
                    var totalCredit = 0;
                    var runningBalance = 0;

                    api.rows().every(function(rowIdx, tableLoop, rowLoop) {
                        var data = this.data();
                        var debit = parseFloat(data.debit) || 0;
                        var credit = parseFloat(data.credit) || 0;

                        totalDebit += debit;
                        totalCredit += credit;

                        runningBalance += credit + debit;

                        // console.log('totalDebit', totalDebit);
                        // console.log('totalCredit', totalCredit);
                        // console.log('runningBalance', runningBalance);

                        var balanceText = ((runningBalance)).toFixed(2);

                        if (runningBalance > 0) {
                            balanceText += ' CR';
                        } else if (runningBalance < 0) {
                            balanceText += ' DR';
                        }


                        var balanceCell = api.cell({ row: rowIdx, column: 5 }).node();
                        $(balanceCell).html(balanceText);
                    });

                    $('#totalDebit').text(totalDebit.toFixed(2));
                    $('#totalCredit').text(totalCredit.toFixed(2));
                    $('#totalRunningBalance').text((runningBalance).toFixed(2));
                },
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var totalDebit = api.column(3).data().reduce(function(a, b) {
                        a = parseFloat(a) || 0;
                        b = parseFloat(b) || 0;
                        return a + b;
                    }, 0);

                    var totalCredit = api.column(4).data().reduce(function(a, b) {
                        a = parseFloat(a) || 0;
                        b = parseFloat(b) || 0;
                        return a + b;
                    }, 0);

                    var totalRunningBalance = 0;
                    var runningBalance = 0;
                    api.rows().every(function(rowIdx) {
                        var data = this.data();
                        var debit = parseFloat(data.debit) || 0;
                        var credit = parseFloat(data.credit) || 0;
                        runningBalance += credit - debit;
                    });

                    totalRunningBalance = runningBalance;




                    $(api.column(3).footer()).html(totalDebit.toFixed(2));
                    $(api.column(4).footer()).html(totalCredit.toFixed(2));
                    $('#totalRunningBalance').text(totalRunningBalance.toFixed(2));
                    $('#openingBalance').text((totalDebit + totalCredit).toFixed(2));
                    $('#outstanding').text((totalRunningBalance).toFixed(2));
                    $('#outstandingBalance').text((totalRunningBalance).toFixed(2));

                    if ((totalRunningBalance) > 0) {
                        $('.btn-outline-danger').show();
                    } else {
                        $('.btn-outline-danger').hide();
                    }
                }
            });
        });
    </script>




@endsection
