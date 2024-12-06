<html>

<head>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    .header {
        text-align: center;
        margin-bottom: 1px;
    }

    .header h1 {
        font-size: 24px;
        margin-bottom: 0px;
        font-weight: bold;
    }
    .header h4 {
        margin-bottom: 40px;
    }
    .header p {
        margin: 5px 0;
        font-size: 14px;
    }

    .name-date {
        margin-top: 50px;
        width: 100%;
    }

    .name {
        text-align: left;
    }

    .date {
        text-align: right;
    }

    .amount-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .amount-table thead,
    .amount-table tfoot {
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
    }

    .amount-table tr td:first-child,
    .amount-table tr th:first-child {
        text-align: left;
    }

    .amount-table tr td:last-child,
    .amount-table tr th:last-child {
        text-align: right;
    }

    .amount-summary {
        margin-top: 10px;
        font-size: 18px;
        font-weight: normal;
    }

    .amount-summary p {
        margin-bottom: 0;
    }

    .footer {
        text-align: right;
    }

    .footer p {
        text-transform: uppercase;
        margin-top: 55px;
        font-size: 20px;
    }

    .footer_s {
        margin: 50px 0 0 0;
    }
    .footer_s span{
        padding: 0 10px;
        font-size: 20px;
    }

    </style>
</head>
<body>
<div class='header'>
        <span style="text-decoration: underline; margin-bottom: 1px;">Bill</span>
        <h1 style="margin-bottom: 2px;">{{ $ledger_data->company_name }}</h1>
        <span>{{ $ledger_data->address }}</span>
        <table class='name-date'>
            <tbody>
                <tr>
                    <td class='name'>Bill No.: {{($ledger_data->voucher_number)}}</td>
                    <td class='date'>Month: {{ date('F - Y', strtotime($ledger_data->voucher_date)) }}</td>
                    <td class='date'>Date: {{ date('d-m-Y', strtotime($ledger_data->voucher_date)) }}</td>
                </tr>
                <tr>
                    <td class='name'></td>
                    <td class='date'></td>
                    <td class='date'>Due Date: {{ \Carbon\Carbon::parse($ledger_data->voucher_date)->addDays(10)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td class='name'><strong>{{ $ledger_data->ledger_name }}</strong></td>
                    <td class='date'></td>
                    <td class='date'><strong>Flat No.: {{($ledger_data->alias1)}}<strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <table class='amount-table'>
        <thead>
            <tr>
                <th>Particular</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
            $totalAmount = 0;
            @endphp

            @foreach ($credits as $credit)
            <tr>
                <td>{{$credit->ledger_name}}</td>
                <td>{{$credit->amount}}</td>
                @php
                $totalAmount += $credit->amount;
                @endphp
            </tr>
            @endforeach

        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td>{{ $totalAmount }}</td>
            </tr>
            <tr>
                <td>Previous Dues:</td>
                <td>{{ $prev_balance }}</td>
            </tr>
            <tr>
                <td>Grand Total:</td>
                <td>{{ $curr_balance }}</td>
            </tr>
        </tfoot>
    </table>
    <div class='amount-summary'>
        <?php 
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            // $sign = ($curr_balance < 0) ? 'Negative ' : '';
            $curr_balance_words = ucwords($formatter->format(abs($curr_balance)));
        ?>
        <span>Rs {{( $curr_balance_words) }}</span><br>
        <span>1. If any discrepancy Found in Bill, Kindly Advice Committee.</span><br>
        <span>2. Bill to be Paid by Due Date.</span>
    </div>
    <div class='footer'>
        <p>For {{$ledger_data->company_name}}</p>
    </div>
    <div style="text-align: right; margin-top: 50px;">
        <p class='footer_s'>
            <span>Checked By &nbsp;&nbsp;</span>
            <span>Secretary &nbsp;&nbsp;</span>
            <span>Treasurer</span>
        </p>
    </div>
</body>

</html>