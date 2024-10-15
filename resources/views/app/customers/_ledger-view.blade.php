<div class="col-lg-12">
        <div class="col-lg-12 mb-2">
            <div class="row">
                <div class="col-lg-9">
                </div>
                <div class="col-lg-3">
                    <form id="dateRangeForm">
                        {{-- <input type="text" id="date_range" name="date_range" class="form-control date-range" placeholder="Select Date Range"> --}}
                        <div class="input-group">
                            <input type="text" id="date_range" name="date_range" class="form-control date-range" placeholder="Select Date Range">
                            <button type="button" id="resetDateRange" class="btn btn-outline-secondary">
                                <i class="fadeIn animated bx bx-refresh" aria-hidden="true"></i> 
                            </button>
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
