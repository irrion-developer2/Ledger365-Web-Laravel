<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>
<body>
    <a href="{{ route('view-pdf', ['voucher_id' => $ledger_data->voucher_id, 'ledger_id' => $ledger_data->ledger_id]) }}" 
        class="btn btn-warning btn-sm ms-2" 
        target="_blank">
         Pdf
     </a>
     
    
    <a data-toggle="modal" 
        data-target="#viewModal" 
        data-ledger-name="{{ $ledger_data->ledger_name }}" 
        data-voucher-date="{{ $ledger_data->voucher_date }}"
        data-credits="{{ $credits }}" 
        data-curr-balance="{{ $curr_balance }}"
        data-company-name="{{ $ledger_data->company_name }}"
        class="btn btn-info btn-sm view-btn ms-2">
            Message
    </a>

    <a href="{{ route('send-email', ['voucher_id' => $ledger_data->voucher_id]) }}" class="btn btn-success btn-sm ms-2">
        Send
    </a>

    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Message</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).on('click', '.view-btn', function() {
            $('#viewModal').modal('show');
        });

        $(document).on('click', '.view-btn', function () {
            const ledgerName = $(this).data('ledger-name');
            const voucherDate = $(this).data('voucher-date');
            const credits = $(this).data('credits');
            const currBalance = $(this).data('curr-balance');
            const companyName = $(this).data('company-name');
            
            $('#viewModal .modal-body').html(`
                <p>Dear Member, ${ledgerName}, bill for the month of <br>
                ${new Date(voucherDate).toLocaleString('default', { month: 'long', year: 'numeric' })} of 
                <b>Rs ${credits}</b> has been generated on ${voucherDate}. <br>
                Total amount to be paid <b>Rs ${currBalance}</b> on or before the due date. <br>${companyName}</p>
            `);
        });
    </script>

    

</body>
</html>

