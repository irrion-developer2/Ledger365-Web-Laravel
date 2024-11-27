<div class="accordion-item">
    <h2 class="accordion-header" id="headingFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
            <i class="bx bx-pie-chart-alt fs-4"></i>&nbsp; Stock On Hand Summary
        </button>   
    </h2>
    <div id="collapseFour" class="accordion-collapse collapse show" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            <div class="col-lg-12">


                    <div class="row">
                       
                        <div class="table-responsive table-responsive-scroll  border-0">
                            <table class="table table-striped" id="stock-hand-table" width="100%">
                                <thead>
                                    <tr>
                                        <td>Date</td>
                                        <td>Transaction Type</td>
                                        <td>Transaction</td>
                                        <td>Debit</td>
                                        <td>Credit</td>
                                        <td>Running Balance</td>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Totals</th>
                                        <th colspan="2"></th>
                                        <th id="total-debit" style="text-align:right"></th>
                                        <th id="total-credit" style="text-align:right"></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                
                
            </div>
        </div>
    </div>
</div>