<html>

<head>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .header h1 {
        font-size: 24px;
        margin: 0;
        font-weight: bold;
    }

    .header p {
        margin: 5px 0;
        font-size: 14px;
    }

    .name-date {
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
        margin-top: 20px;
        font-size: 14px;
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
        margin-top: 30px;
        font-size: 22px;
    }
    </style>
</head>
<body>
<div class='header'>
        <h1>{{ $ledger_data->company_name }}</h1>
        <p>Month: {{ date('F Y', strtotime($ledger_data->voucher_date)) }}</p>
        <table class='name-date'>
            <tbody>
                <tr>
                    <td class='name'><strong>{{ $ledger_data->ledger_name }}</strong></td>
                    <td class='date'>{{ $ledger_data->voucher_date }}</td>
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
                <td>Previous Dues</td>
                <td>{{ $prev_balance }}</td>
            </tr>
            <tr>
                <td>Grand Total:</td>
                <td>{{ $curr_balance }}</td>
            </tr>
        </tfoot>
    </table>
    <div class='amount-summary'>
        <p>1. If any discrepancy Found in Bill, Kindly Advice Committee.</p>
        <p>2. Bill to be Paid by Due Date.</p>
    </div>
    <div class='footer'>
        <p>from {{$ledger_data->company_name}}</p>
    </div>
</body>

</html>