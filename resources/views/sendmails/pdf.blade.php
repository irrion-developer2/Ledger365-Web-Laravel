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
        margin-bottom: 5px;
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

    .footer_s {
        display: flex;
        justify-content: flex-end;
        gap: 100px;
        margin: 0;
        font-size: 20px;
    }

    </style>
</head>
<body>
<div class='header'>
        <h4 style="text-decoration: underline; margin-bottom: 1px;">Bill</h4>
        <h1>{{ $ledger_data->company_name }}</h1>
        <h4>{{ $ledger_data->address }}</h4>
        <table class='name-date'>
            <tbody>
                <tr>
                    <td class='name'>Bill No.: {{($ledger_data->voucher_number)}}</td>
                    <td class='date'>Month: {{ date('F Y', strtotime($ledger_data->voucher_date)) }}</td>
                    <td class='date'>Date: {{ $ledger_data->voucher_date }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td class='due-date'>Due Date: {{ \Carbon\Carbon::parse($ledger_data->voucher_date)->addDays(10)->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td><strong>{{ $ledger_data->ledger_name }}</strong></td>
                    <td></td>
                    <td><strong>Flat No.: {{($ledger_data->alias1)}}<strong></td>
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
          <p>{{( $curr_balance_words) }}</p>
        {{-- <p>{{ str_replace('Minus', '-', $curr_balance_words) }}</p> --}}
        <p>1. If any discrepancy Found in Bill, Kindly Advice Committee.</p>
        <p>2. Bill to be Paid by Due Date.</p>
    </div>
    <div class='footer'>
        <p>For {{$ledger_data->company_name}}</p>
    </div>
    <div style="text-align: right; margin-top: 100px;">
        <p class='footer_s'>
            <span>Checked By</span>
            <span>Secretary</span>
            <span>Treasurer</span>
        </p>
    </div>
</body>

</html>