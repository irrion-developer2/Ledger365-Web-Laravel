<div class="col-lg-12">
    <div id="vue-datepicker-app" class="mb-2">
        <!-- Vue component should have id exactly as specified in Vue instance -->
        <div class="d-lg-flex align-items-center gap-2">
            <div class="col-lg-9"></div>
            <div class="col-lg-3">
                <form id="dateRangeForm">
                    <div class="input-group">
                        <date-picker 
                                v-model="dateRange"
                                :range="true" 
                                format="YYYY-MM-DD" 
                                :number-of-months="2" 
                                placeholder="Select Date Range"
                                :time-picker="false"
                                :min-date="firstVoucherDate"
                                :max-date="lastVoucherDate"
                                value-type="format">
                        </date-picker>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="voucherEntriesTable" class="table table-striped table-bordered" style="width: 100%;">
            <thead>
                <tr>
                    <th>Voucher Date</th>
                    <th>Particulars</th>
                    <th>Voucher Number</th>
                    <th>Voucher Type</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Running Balance</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th id="totalDebit"></th>
                    <th id="totalCredit"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
