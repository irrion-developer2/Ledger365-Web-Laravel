<div class="accordion-item">
    <h2 class="accordion-header" id="headingThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
            <i class="bx bx-star fs-4"></i>&nbsp; Sales & Purchase Overview 
        </button>
    </h2>
    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            <div class="col-lg-12">


                <p class="pb-4"><strong>Sales Summary</strong></p>


                    <div class="row">
                        <div class="col-lg-3 pb-5">
                            Std. Sales Price
                            <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Avg. Sales Price
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                        $firstRate = $stockItemVoucherSaleItem->pluck('rate')->filter()->first();
                                    @endphp ₹{{ number_format($firstRate ?? 0.00, 2) }}
                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Total Sales Invoices
                            <p class="mb-0 font-16 pt-2"><strong>{{ $stockItemVoucherSaleItem->count() ?? '-' }} | {{ $stockItemVoucherSaleItem->sum('billed_qty') ?? '-' }} @php
                                $firstUnit = $stockItemVoucherSaleItem->pluck('unit')->filter()->first();
                            @endphp {{ $firstUnit ?? '-' }}
                            
                            </strong></p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Total Sales
                            <p class="mb-0 font-16 pt-2"><strong>₹{{ number_format(abs($stockItemVoucherSaleItem->sum('amount') ?? 0.00), 2) }}
                            </strong></p>
                        </div>
                    </div>
                
                    <div class="row">
                        <div class="col-lg-3 pb-5">
                            Min Sales Rate
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                        $minRate = $stockItemVoucherSaleItem->pluck('rate')->filter()->min();
                                    @endphp ₹{{ number_format($minRate ?? 0.00, 2) }}
                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Max Sales Rate
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                        $maxRate = $stockItemVoucherSaleItem->pluck('rate')->filter()->max();
                                    @endphp ₹{{ number_format($maxRate ?? 0.00, 2) }}
                                </strong>
                            </p>
                        </div>

                            @php
                                $lastVoucherDate = null;
                                foreach ($stockItemVoucherSaleItemConnect as $itemConnect) {
                                    if (isset($itemConnect['tally_voucher_items']) && !empty($itemConnect['tally_voucher_items'])) {
                                        $sortedItems = collect([$itemConnect['tally_voucher_items']])->sortBy('rate')->last();
                                        $tallyVoucher = $itemConnect['tally_vouchers']->firstWhere('id', $sortedItems->tally_voucher_id);
                                        
                                        if ($tallyVoucher) {
                                            $lastVoucherDate = \Carbon\Carbon::parse($tallyVoucher->voucher_date)->format('j F Y');
                                        }
                                    }
                                }
                            @endphp
                            @if($lastVoucherDate)
                                <div class="col-lg-3 pb-5">
                                    Last Sale Date
                                    <p class="mb-0 font-16 pt-2">
                                        <strong>{{ $lastVoucherDate }}</strong>
                                    </p>
                                </div>
                            @endif

                            @php
                                $lastVoucherRate = null;
                                foreach ($stockItemVoucherSaleItemConnect as $itemConnect) {
                                    if (isset($itemConnect['tally_voucher_items']) && !empty($itemConnect['tally_voucher_items'])) {
                                    
                                        $sortedItems = collect([$itemConnect['tally_voucher_items']])->sortBy('rate')->last();
                                        // $tallyVoucher = $itemConnect['tally_vouchers']->firstWhere('id', $sortedItems->tally_voucher_id);
                                        if ($sortedItems) {
                                            $lastVoucherRate = $sortedItems->rate;
                                        }
                                    }
                                }
                            @endphp

                            @if($lastVoucherRate)
                                <div class="col-lg-3 pb-5">
                                    Last Sale Rate
                                    <p class="mb-0 font-16 pt-2">
                                        <strong>₹{{ number_format($lastVoucherRate ?? 0.00, 2) }}</strong>
                                    </p>
                                </div>
                            @endif
                    </div>

                    <hr>

                    <p class="pb-4"><strong>Purchase Summary</strong></p>

                    <div class="row">
                        <div class="col-lg-3 pb-5">
                            Std. Purchase Price
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    {{ $stockItem->name ?? '-' }}
                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Avg. Purchase Price
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                    $firstRate = $stockItemVoucherPurchaseItem->pluck('rate')->filter()->first();
                                    @endphp 
                                    @if($firstRate !== null && $firstRate != 0)
                                        ₹{{ $firstRate !== null && $firstRate != 0 ? number_format($firstRate, 2) : '-' }}
                                    @else
                                        {{ $firstRate ?? '-' }}
                                    @endif

                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Total Purchase Invoices
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    {{ $stockItemVoucherPurchaseItem->count() ?? '-' }} 
                                    | 
                                    {{ $stockItemVoucherPurchaseItem->sum('billed_qty') ?? '-' }} 
                                    @php
                                        $firstUnit = $stockItemVoucherPurchaseItem->pluck('unit')->filter()->first();
                                    @endphp {{ $firstUnit ?? '-' }}

                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Total Purchase
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    ₹{{ number_format(abs($stockItemVoucherPurchaseItem->sum('amount') ?? 0.00), 2) }}
                                </strong>
                            </p>
                        </div>
                    </div>
                
                    <div class="row">
                        <div class="col-lg-3 pb-5">
                            Min Purchase Rate
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                        $minRate = $stockItemVoucherPurchaseItem->pluck('rate')->filter()->min();
                                    @endphp 

                                    @if($minRate !== null && $minRate != 0)
                                        ₹{{ $minRate !== null && $minRate != 0 ? number_format($minRate, 2) : '-' }}
                                    @else
                                        {{ $minRate ?? '-' }}
                                    @endif
                                </strong>
                            </p>
                        </div>
                        <div class="col-lg-3 pb-5">
                            Max Purchase Rate
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @php
                                        $maxRate = $stockItemVoucherPurchaseItem->pluck('rate')->filter()->max();
                                    @endphp 
                                    @if($maxRate !== null && $maxRate != 0)
                                        ₹{{ $maxRate !== null && $maxRate != 0 ? number_format($maxRate, 2) : '-' }}
                                    @else
                                        {{ $maxRate ?? '-' }}
                                    @endif
                                </strong>
                            </p>
                        </div>


                        @php
                            $lastVoucherDate = null;
                            foreach ($stockItemVoucherPurchaseItemConnect as $itemConnect) {
                                if (isset($itemConnect['tally_voucher_items']) && !empty($itemConnect['tally_voucher_items'])) {
                                    $sortedItems = collect([$itemConnect['tally_voucher_items']])->sortBy('rate')->last();
                                    $tallyVoucher = $itemConnect['tally_vouchers']->firstWhere('id', $sortedItems->tally_voucher_id);
                                    
                                    if ($tallyVoucher) {
                                        $lastVoucherDate = \Carbon\Carbon::parse($tallyVoucher->voucher_date)->format('j F Y');
                                    }
                                }
                            }
                        @endphp
                        
                        <div class="col-lg-3 pb-5">
                            Last Purchase Date
                            <p class="mb-0 font-16 pt-2">
                                <strong>{{ $lastVoucherDate }}</strong>
                            </p>
                        </div>
                        
                        @php
                            $lastVoucherRate = null;
                            foreach ($stockItemVoucherPurchaseItemConnect as $itemConnect) {
                                if (isset($itemConnect['tally_voucher_items']) && !empty($itemConnect['tally_voucher_items'])) {
                                
                                    $sortedItems = collect([$itemConnect['tally_voucher_items']])->sortBy('rate')->last();
                                    // $tallyVoucher = $itemConnect['tally_vouchers']->firstWhere('id', $sortedItems->tally_voucher_id);
                                    if ($sortedItems) {
                                        $lastVoucherRate = $sortedItems->rate;
                                    }
                                }
                            }
                        @endphp

                        <div class="col-lg-3 pb-5">
                            Last Purchase Rate
                            <p class="mb-0 font-16 pt-2">
                                <strong>
                                    @if($lastVoucherRate !== null && $lastVoucherRate != 0)
                                        ₹{{ $lastVoucherRate !== null && $lastVoucherRate != 0 ? number_format($lastVoucherRate, 2) : '-' }}
                                    @else
                                        {{ $lastVoucherRate ?? '-' }}
                                    @endif
                                </strong>
                            </p>
                        </div>

                    </div>
               

            </div>
        </div>
    </div>
</div>