@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .table-responsive-scroll {
        max-height: 500px; /* Set to your preferred height */
        overflow-y: auto;
        overflow-x: hidden !important; /* Optional, hides horizontal scrollbar */
        border: 1px solid #ddd;
    }
</style>

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
                        <li class="breadcrumb-item active" aria-current="page">Voucher Head</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-lg-flex align-items-center gap-2">
                    <h4 class="my-1 text-info">{{ $voucherHead->ledger_name }} </h4>
                </div>

                <div class="table-responsive table-responsive-scroll border-0">
                    <table class="table table-striped" id="voucher-head-table" width="100%">
                        <thead>
                            <tr>
                                <td>Date</td>
                                <td>Transaction Type</td>
                                <td>Transaction</td>
                                <td>Debit</td>
                                <td>Credit</td>
                                <td>Running Balance</td>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th colspan="2"></th>
                                <th id="total-debit" style="text-align:right"></th>
                                <th id="total-credit" style="text-align:right"></th>
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
@section('script')
@include('layouts.includes.datatable-js-css')
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
<script>
    $(document).ready(function() {
        let runningBalance = 0;

        $('#voucher-head-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: '{{ route('reports.VoucherHead.data', $voucherHeadId) }}',
            columns: [
                {
                    data: 'voucher_date',
                    name: 'voucher_date'
                },
                { data: 'voucher_type_name', name: 'voucher_type_name', className: 'text-center' },
                { data: 'voucher_number', name: 'voucher_number', className: 'text-center',
                    {{--  render: function(data, type, row) {
                        return '<a href="{{ url('reports/VoucherItem') }}/' + row.voucher_id + '">' + data + '</a>';
                    }  --}}
                },
                { data: 'debit', name: 'debit', className: 'text-end' },
                { data: 'credit', name: 'credit', className: 'text-end' },
                { data: 'running_balance', name: 'running_balance', className: 'text-end', orderable: false, searchable: false }
            ],
            drawCallback: function(settings) {
                var api = this.api();
                let rows = api.rows({ page: 'current' }).data();

                var DebitToTotal = 3;
                var CreditToTotal = 4;
                var RunningBalanceToTotal = 5;

                var Debittotal = api.column(DebitToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var Credittotal = api.column(CreditToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                var RunningBalancetotal = api.column(RunningBalanceToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);


                $(api.column(DebitToTotal).footer()).html(jsIndianFormat(Math.abs(Debittotal), 2));
                $(api.column(CreditToTotal).footer()).html(jsIndianFormat(Math.abs(Credittotal), 2));
                $(api.column(RunningBalanceToTotal).footer()).html(jsIndianFormat(Math.abs(RunningBalancetotal), 2));

                {{--  $(api.column(3).footer()).html(totalDebit.toFixed(2));
                $(api.column(4).footer()).html(totalCredit.toFixed(2));
                $(api.column(5).footer()).html(Math.abs(runningBalance).toFixed(2));  --}}
            }
        });
        
        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

    });
</script>
@endsection