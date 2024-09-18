
        <div id="invoice">
            <div class="invoice overflow-auto">
                <div style="min-width: 600px">
                    <header>
                        <div class="row">
                            <div class="col">
                                <a href="javascript:;">
                                    <img src="{{ asset('assets/images/precise/imageedit_4_4313936362.png') }}" width="80" alt="" />
                                </a>
                            </div>
                            <div class="col company-details">
                                @foreach($companies as $company)
                                    <h2 class="name">
                                        <a href="javascript:;">{{ $company->name }}</a>
                                    </h2>
                                    <div>State Name : {{ $company->state }}</div>
                                @endforeach
                            </div>
                        </div>
                    </header>
                    <main>

                        <div class="row contacts">
                            <div class="col invoice-to">
                                <div class="email">Invoice No. : <b>{{ $voucherItem->voucher_number }}</b>
                                </div>
                            </div>
                            <div class="col invoice-to text-center">
                                <div class="email"><b>{{ $voucherItem->voucher_type }} Voucher</b>
                                </div>
                            </div>
                            <div class="col invoice-details">
                                <div class="date">Date : <b>{{ \Carbon\Carbon::parse($voucherItem->voucher_date)->format('j F Y') }}</b></div>
                            </div>
                        </div>

                        
                        <table id="receipt-datatable" class="table " style="width:100%">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Amount</th>
                                    <th id="amount">Amount</th>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div>
                            <b>Through : </b><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            @foreach ($bankAcc as $ledger)
                                {{ $ledger->ledger_name }}<br>
                            @endforeach
                            <b>Bank Transaction Details : </b><br>
                                @foreach ($bankAcc as $ledger)
                                    {{ $ledger->ledger_name }}<br>
                                @endforeach
                                @foreach ($bankAcc as $ledgerData)
                                    {{ $ledgerData->ledger->tax_type ?? 'No ledger found' }}
                                @endforeach
                                @foreach ($bankAcc as $ledger)
                                    <span style="margin-left: 40%;"><b>₹ {{ number_format(abs($ledger->amount), 2) }}</b></span><br>
                                @endforeach
                            {{-- <b>Amount (in words) : </b><br>
                                @foreach ($bankAcc as $ledger)
                                    <span style="margin-left: 40%;"><b>₹ {{ number_format(abs($ledger->amount), 2) }}</b></span>
                                @endforeach --}}
                                
                        </div>

                        <footer class="" style="border-top: none;margin-bottom: 25%;">
                            <div class="text-end">
                                <p>Authorised Signatory</p>
                            </div>
                        </footer>
                        
                    </main>



                    <footer>
                        <div class="row text-center"> 
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <p>Prepared by</p>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <p>Checked by</p>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <p>Verified by</p>
                            </div>
                        </div>
                    </footer>
                    
                </div>
                <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
                <div></div>
            </div>
        </div>


@push('javascript')
<script>
    $(document).ready(function() {
        $('#receipt-datatable').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: '<"top"f>rt<"bottom"lp><"clear">', 
            ajax: '{{ route('reports.VoucherItemReceiptInvoice.data', $voucherItemId) }}',
            columns: [
                { data: 'ledger_name', name: 'ledger_name' },
                {
                    data: 'amount', name: 'amount', className: 'text-end',
                    render: function(data, type, row) {
                        return data ? parseFloat(Math.abs(data)).toFixed(2) : '0.00';
                    }
                }

            ],
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Helper function to parse and clean values
                var intVal = function(i) {
                    return typeof i === 'string' ?
                        i.replace(/[\₹,]/g, '') * 1 :
                        typeof i === 'number' ?
                            i : 0;
                };

                var amount = api.column(1, { page: 'all' }) .data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                $('#amount').text(Math.abs(amount).toFixed(2));
            }
        });
    });
</script>
<script>
    function printInvoice() {
        const printContents = document.getElementById('invoice').innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>
@endpush