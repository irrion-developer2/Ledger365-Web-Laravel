<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>

    <style>
         body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .receipt-title {
            text-align:center;
            font-size: 18px;
            margin: 0;
            text-decoration:underline;
        }

        .org-name {
            text-align: center;
            font-size: 24px;
            margin-bottom:2PX;
        }

        .address {
            text-align: center;
            font-size: 14px;
            margin: 0;
        }

        .details {
            width:100%;
            margin-top: 50px;
        }

        .thanks-text {
            font-size: 18px;
            margin: 20px 0;
        }

        .amount {
            font-size: 20px;
            font-weight: bold;
            margin:0;
        }

        .note {
            font-size: 18px;
            margin-bottom: 30px;
            margin-top:0;
        }

        .footer {
            text-align: right;
            margin-top: 30px;
        }
        .footer p {
            text-transform:uppercase;
            font-size:20px;
        }

        .signatures {
            margin: 50px 0 0 0;
        }

        .signatures span {
            padding: 0 10px;
            font-size: 20px;
        }

        .disclaimer {
            font-size: 18px;
            text-align: center;
            position: absolute;
            bottom: 10px;
            width: 100%;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <p class="receipt-title">Receipt</p>
        <h1 class="org-name">{{ $receipt->company_name }}</h1>
        <p style="text-align:center; margin:0;">{{ $receipt->address }}</p>
        
        <table class="details">
            <tr>
                <td>Receipt No.: {{$receipt->voucher_number}}</td>
                <td style="text-align: right;">Date: {{date('d-m-Y', strtotime($receipt->voucher_date))}}</td>
            </tr>
        </table>
        
        <p class="thanks-text">
            RECEIVED with thanks from {{ $receipt->ledger_name }} Flat No. : {{ $receipt->alias1 }}<br>
            the sum of Rupees {{ $curr_balance_words }} Only by Ch. No. : NEFTXX<br>
            {{ $recipt_ledger_name->ledger_name }} For Month {{ date('F y', strtotime($receipt->voucher_date)) }}
        </p>
        
        <p class="amount">Rs. <b>{{ number_format($receipt->amount, 2, '.', ',') }}</b></p>
        <p class="note">Subject to Realisation of Cheque.</p>
        
        <div class="footer">
            <p>For {{ $receipt->company_name }}</p>
            <div class="signatures">
                <span>Checked By</span>
                <span>Secretary</span>
                <span>Treasurer</span>
            </div>
        </div>
        <p class="disclaimer">This is a computer-generated receipt. no signature required.</p>
    </div>
</body>
</html>
