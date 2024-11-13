<div class="table-responsive table-responsive-scroll border-0">
                        
    <table id="paymentReceived-datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Voucher Date</th>
                <th>Voucher Number</th>
                <th>Voucher Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            {{-- Data will be populated by AJAX --}}
        </tbody>
        <tfoot>
            <tr>
                <th>Voucher Date</th>
                <th>Voucher Number</th>
                <th>Voucher Type</th>
                <th>Amount</th>
            </tr>
        </tfoot>
    </table>

</div>
@push('javascript')
<script>
    $(document).ready(function() {
        $('#paymentReceived-datatable').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: '{{ route('reports.VoucherItemReceipt.data', $voucherItemId) }}',
            columns: [
                { data: 'voucher_date', name: 'voucher_date' },
                { data: 'voucher_number', name: 'voucher_number' ,
                    render: function(data, type, row) {
                        return '<a href="{{ url('reports/VoucherItem') }}/' + row.voucher_id + '">' + data + '</a>';
                    } 
                },
                { data: 'voucher_type_name', name: 'voucher_type_name' },
                {
                    data: 'amount', name: 'amount', className: 'text-end',
                    render: function(data, type, row) {
                        return data ? parseFloat(Math.abs(data)).toFixed(2) : '0.00';
                    }
                }

            ]
        });
    });
</script>
@endpush