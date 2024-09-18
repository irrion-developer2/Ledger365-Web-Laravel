<div class="accordion-item">
    <h2 class="accordion-header" id="headingTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            <i class="bx bx-trending-up fs-4"></i>&nbsp; Sales & Purchase Trend
        </button>   
    </h2>
    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            <div class="col-lg-12">


                    <div class="row">
                        <div class="col-lg-3 pb-5">
                            Total Sales
                            <p class="mb-0 font-16 pt-2"><strong>₹{{ number_format(abs($stockItemVoucherSaleItem->sum('amount') ?? 0.00), 2) }}</strong></p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Total Purchase
                            <p class="mb-0 font-16 pt-2"><strong>₹{{ number_format(abs($stockItemVoucherPurchaseItem->sum('amount') ?? 0.00), 2) }}</strong></p>
                        </div>
                    </div>
                
                
            </div>
        </div>
    </div>
</div>