@if($totalCountItems > 0)
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingTwo">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                <i class="bx bx-cube fs-4"></i>&nbsp;  Items & Services <p class="" style="margin-left: auto;">{{ $totalCountItems }} Items</p>
            </button>
        </h2>
        <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
            <div class="accordion-body">    
                <div class="table-responsive table-responsive-scroll  border-0">
                    <table class="table table-striped" id="sale-item-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <td>Name</td>
                                <td>HSN</td>
                                <td>QTY</td>
                                <td>&#8377 Rate</td>
                                <td>GST</td>
                                <td>Disc %</td>
                                <td>&#8377 Amount</td>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="5"></th>
                                <th style="text-align:right">Subtotal</th>
                                <th style="text-align:right" id="subtotal"></th>
                            </tr>
                            @foreach($gstVoucherHeads as $gstVoucherHead)
                                <tr>
                                    <th colspan="5"></th>
                                    <th style="text-align:right">{{ $gstVoucherHead->ledger_name }}</th>
                                    <th style="text-align:right" data-amount="{{ $gstVoucherHead->amount }}">{{ number_format(abs($gstVoucherHead->amount), 2) }}</th>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="5"></th>
                                <th style="text-align:right">Total Invoice Value</th>
                                <th style="text-align:right" id="totalInvoiceValue"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
