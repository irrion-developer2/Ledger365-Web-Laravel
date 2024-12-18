{{-- Profit & Loss A/c --}}
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>
    <script>
        $(document).ready(function() {
            var openingStockTable = $('#balanceStockSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheetProfitLoss.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                        render: function(data, type, row) {
                            var url = '{{ route("stock-items.index") }}';
                            return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                        }
                    },
                    {data: 'opening_value', name: 'opening_value'},
                ]
            });

            var expenseTable = $('#balanceExpenseSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheetProfitLossExpense.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.GeneralGroupLedger.details", ":id") }}';
                        url = url.replace(':id', row.id); // Handle missing id
                        return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                    }
                },
                    {data: 'amount', name: 'amount'},
                ],
                initComplete: function () {
                    this.api().rows().every(function () {
                        var data = this.data();
                        if (data.account_type !== 'Expense') {
                            this.remove();
                        }
                    });
                    this.api().draw();
                    calculateTotals();
                }
            });

            var closingStockTable = $('#balanceClosingStockSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheetProfitLossClosingStock.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                        render: function(data, type, row) {
                            var url = '{{ route("stock-items.index") }}';
                            return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                        }
                    },
                    {data: 'closing_value', name: 'closing_value'},
                ]
            });

            var revenueTable = $('#balanceRevenueSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheetProfitLossExpense.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                    render: function(data, type, row) {
                        var url = '{{ route("reports.GeneralGroupLedger.details", ":id") }}';
                        url = url.replace(':id', row.id); // Handle missing id
                        return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                    }
                },
                    {data: 'amount', name: 'amount'},
                ],
                initComplete: function () {
                    this.api().rows().every(function () {
                        var data = this.data();
                        if (data.account_type !== 'Revenue') {
                            this.remove();
                        }
                    });
                    this.api().draw();
                    calculateTotals();
                }
            });

            var liabilitieTable = $('#balanceLiabilitieSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheet.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                        render: function(data, type, row) {
                            var url = '{{ route("reports.BalanceSheet.Liability", ":guid") }}';
                            url = url.replace(':guid', row.guid);
                            return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                        }
                    },
                    {data: 'amount', name: 'amount'},
                ],
                
                initComplete: function () {
                    this.api().rows().every(function () {
                        var data = this.data();
                        if (data.account_type !== 'Liability') {
                            this.remove();
                        }
                    });
                    this.api().draw();
                    calculateTotals();
                }
            });

            var assetTable = $('#balanceAssetSheet-datatable').DataTable({
                fixedColumns: {
                    start: 1,
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: false,
                searching: false,
                order: false, 
                dom: '<"top"f>rt<"bottom"lp><"clear">', 
                ajax: {
                    url: "{{ route('reports.BalanceSheet.get-data') }}",
                    type: 'GET'
                },
                columns: [
                    {data: 'name', name: 'name',
                        render: function(data, type, row) {
                            var url = '{{ route("reports.BalanceSheet.Liability", ":guid") }}';
                            url = url.replace(':guid', row.guid);
                            return '<a href="' + url + '" style="color: #4c5258;">' + data + '</a>';
                        }},
                    {data: 'amount', name: 'amount'},
                ],
                
                initComplete: function () {
                    this.api().rows().every(function () {
                        var data = this.data();
                        if (data.account_type !== 'Asset') {
                            this.remove();
                        }
                    });
                    this.api().draw();
                    calculateTotals();
                }
            });


            function calculateTotals() {
                var totalOpeningValue = 0;
                openingStockTable.rows().every(function () {
                    var data = this.data();
                    var openingValue = parseFloat(data.opening_value.replace(/,/g, ''));
                    if (!isNaN(openingValue)) {
                        totalOpeningValue += openingValue;
                    }
                });

                var totalExpenseAmount = 0;
                expenseTable.rows().every(function () {
                    var data = this.data();
                    var amount = parseFloat(data.amount.replace(/,/g, ''));
                    if (!isNaN(amount)) {
                        totalExpenseAmount += amount;
                    }
                });

                var totalClosingValue = 0;
                closingStockTable.rows().every(function () {
                    var data = this.data();
                    var closingValue = parseFloat(data.closing_value.replace(/,/g, ''));
                    if (!isNaN(closingValue)) {
                        totalClosingValue += closingValue;
                    }
                });

                var totalRevenueAmount = 0;
                revenueTable.rows().every(function () {
                    var data = this.data();
                    var amount = parseFloat(data.amount.replace(/,/g, ''));
                    if (!isNaN(amount)) {
                        totalRevenueAmount += amount;
                    }
                });

                var totalAssetAmount = 0;
                assetTable.rows().every(function () {
                    var data = this.data();
                    var amount = parseFloat(data.amount.replace(/,/g, ''));
                    if (!isNaN(amount)) {
                        totalAssetAmount += amount;
                    }
                });

                var totalLiabilityAmount = 0;
                liabilitieTable.rows().every(function () {
                    var data = this.data();
                    var amount = parseFloat(data.amount.replace(/,/g, ''));
                    if (!isNaN(amount)) {
                        totalLiabilityAmount += amount;
                    }
                });

                var overallExpenseTotal = totalOpeningValue + totalExpenseAmount;
                var overallRevenueTotal = totalClosingValue + totalRevenueAmount;
                var nettLoss = overallExpenseTotal - overallRevenueTotal;

                var overallDiffRevenueTotal = totalClosingValue + totalRevenueAmount;
                var overallDiffExpenseTotal = totalOpeningValue + totalExpenseAmount;

                if (nettLoss < 0) {
                    overallDiffExpenseTotal -= nettLoss;
                } else {
                    overallDiffRevenueTotal += nettLoss;
                }

                $('#Expense').text(jsIndianFormat(overallExpenseTotal));
                $('#Revenue').text(jsIndianFormat(overallRevenueTotal));
                // $('#NettLoss').text(nettLoss.toFixed(3));
                // $('#NettProfit').text(nettLoss.toFixed(3));
                if (nettLoss < 0) {
                    $('#NettLoss').text('0.00');
                    $('#NettProfit').text(jsIndianFormat(nettLoss));
                } else {
                    $('#NettLoss').text(jsIndianFormat(nettLoss));
                    $('#NettProfit').text('0.00');
                }

                if (nettLoss < 0) {
                    $('#ProfitLossAccAsset').text('0.00');
                    $('#ProfitLossAccLia').text(jsIndianFormat(nettLoss));
                } else {
                    $('#ProfitLossAccAsset').text(jsIndianFormat(nettLoss));
                    $('#ProfitLossAccLia').text('0.00');
                }

                
                $('#diffRevenue').text(jsIndianFormat(overallDiffRevenueTotal));
                $('#diffExpense').text(jsIndianFormat(overallDiffExpenseTotal));

                // var ProfitLossAcc = overallExpenseTotal - overallRevenueTotal;
                var overallAssetStockItemTotal = totalAssetAmount + totalClosingValue;

                // var overallAssetTotal = totalAssetAmount + totalClosingValue + ProfitLossAcc;
                // var DiffOpeningBalance = overallAssetTotal - totalLiabilityAmount;
                // var overallLiabilityTotal = totalLiabilityAmount + DiffOpeningBalance;


                // var overallExpenseTotal = totalOpeningValue + totalExpenseAmount;
                // var overallRevenueTotal = totalClosingValue + totalRevenueAmount;
                // var nettLoss = overallExpenseTotal - overallRevenueTotal;

                var overallAssetTotal = totalAssetAmount + totalClosingValue;



                if (nettLoss < 0) {
                    overallLiabilityTotal -= nettLoss;
                } else {
                    overallAssetTotal += nettLoss;
                }

                
                var DiffOpeningBalance = overallAssetTotal - totalLiabilityAmount;
                
                // console.log('DiffOpeningBalance', DiffOpeningBalance);
                

                if (nettLoss >= 0) { 
                    var overallLiabilityTotal = totalLiabilityAmount + DiffOpeningBalance;
                } else {
                    var overallLiabilityTotal = totalLiabilityAmount - nettLoss;
                }
                
                $('#AssetStockItem').text(jsIndianFormat(overallAssetStockItemTotal));

                // $('#DiffOpeningBalance').text(DiffOpeningBalance.toFixed(3));
                if (nettLoss >= 0) { 
                    $('#DiffLiabilitieOpeningBalance').text(jsIndianFormat(DiffOpeningBalance));
                } else {
                    $('#DiffLiabilitieOpeningBalance').text(''); 
                }


                $('#Asset').text(jsIndianFormat(Math.abs(overallAssetTotal)));
                $('#Liability').text(jsIndianFormat(Math.abs(overallLiabilityTotal)));
            }

        });
    </script>
{{-- Profit & Loss A/c --}}

{{-- Balance Sheet --}}

{{-- Balance Sheet --}}