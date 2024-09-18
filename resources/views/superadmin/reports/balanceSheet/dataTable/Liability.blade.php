<div class="table-responsive table-responsive-scroll border-0">
    <table id="balanceLiabilitieSheet-datatable" class="stripe row-border order-column" style="width:100%">
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {{-- Data will be populated by AJAX --}}
        </tbody>
        <tfoot>
            <tr>
                <th>Difference in Opening Balances</th>
                <th id="DiffOpeningBalance"></th>
            </tr>
            <tr>
                <th><a href="{{ route('reports.BalanceSheetProfitLoss') }}" style="color: #4c5258;">Profit & Loss A/c</a></th>
                <th id="ProfitLossAccLia"></th>
            </tr>
        </tfoot>
    </table>
</div>