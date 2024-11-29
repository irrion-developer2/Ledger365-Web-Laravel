<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>

    <style>
        .receipt-title {
            text-align: right;
            font-size: 18px;
            margin: 0;
        }

        .org-name {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .address {
            text-align: center;
            font-size: 14px;
            margin: 0;
            color: #555;
        }

        .details {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            font-size: 14px;
        }

        .thanks-text {
            font-size: 16px;
            margin: 20px 0;
        }

        .amount {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }

        .note {
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            margin-top: 30px;
        }

        .signatures {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }

        .signatures p {
            margin: 0;
            font-size: 14px;
        }

        .disclaimer {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h3 class="receipt-title">Receipt</h3>
        <h1 class="org-name">{{ $receipt->company_name }}</h1>
        
        <div class="details">
            <p>Receipt No.: <b>{{$receipt->voucher_number}}</b></p>
            <p>Date: <b>{{$receipt->voucher_date}}</b></p>
        </div>
        
        <p class="thanks-text">
            RECEIVED with thanks from {{ $receipt->ledger_name }} Flat No. : <b>{{ $receipt->alias1 }}</b><br>
            the sum of Rupees <b>{{ $curr_balance_words }} Only</b> by Ch. No. : <b>NEFTXX</b><br>
            <b>{{ $recipt_ledger_name->ledger_name }}</b> For Month {{ date('F Y', strtotime($receipt->voucher_date)) }}
        </p>
        
        <p class="amount">Rs. <b>{{ $receipt->amount }}</b></p>
        <p class="note">Subject to Realisation of Cheque.</p>
        
        <div class="footer">
            <p>For <b>{{ $receipt->company_name }}</b></p>
            <div class="signatures">
                <p>Checked By</p>
                <p>Secretary</p>
                <p>Treasurer</p>
            </div>
            <p class="disclaimer">This is a computer-generated receipt. no signature required.</p>
        </div>
    </div>
</body>
</html>
