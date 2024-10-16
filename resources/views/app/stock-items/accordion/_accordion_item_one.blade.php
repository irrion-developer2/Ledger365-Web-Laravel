{{-- @php
    $hasDetails = $saleItem->narration || $saleItem->reference_no || $saleItem->reference_date;
@endphp

@if($hasDetails) --}}
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                <i class="bx bx-info-circle fs-4"></i>&nbsp; Info
            </button>
        </h2>
        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
            <div class="accordion-body">
                <div class="col-lg-12">

                    <p class="mb-0 font-16 pb-4"><i class="bx bx-star fs-4"></i>&nbsp; <span>General Details </span> </p>


                        <div class="row">
                            <div class="col-lg-3 pb-5">
                                Stock Name
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Stock Group
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->parent ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Supplier Item Number
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Description
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                        </div>
                    
                        <div class="row">
                            <div class="col-lg-3 pb-5">
                                Opening Stock
                                <p class="mb-0 font-16 pt-2">
                                    <strong>
                                        @if(is_numeric($stockItem->opening_value))
                                            {{ number_format(abs((float)$stockItem->opening_value), 2) }} 
                                        @else
                                            -
                                        @endif
                                        | 
                                        {{ $stockItem->opening_balance ?? '-' }}
                                    </strong>
                                </p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Primary UOM
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->vat_base_unit ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Alternate UOM
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Reorder Level
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-3 pb-5">
                                Stock Category
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->category ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Std Cost Price
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Std Sale Price
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->name ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Alias
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->alias1 ?? '-' }}</strong></p>
                            </div>
                        </div>

                        <hr>

                        <p class="mb-0 font-16"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-percent text-dark"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>&nbsp; <span>Tax Summary</span> </p>

                        <div class="row pt-5">
                            <div class="col-lg-3 pb-5">
                                HSN/SAC Code
                                <p class="mb-0 font-16 pt-2"><strong>{{ $stockItem->hsn_code ?? '-' }}</strong></p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                Tax Group
                                <p class="mb-0 font-16 pt-2">
                                    <strong>
                                        @php
                                            $gstRate = $stockItemVoucherSaleHead[0]['gst_rate'] ?? '-';
                                            // Extract numeric part of the gst_rate and format it
                                            if ($gstRate !== '-') {
                                                $rateValue = floatval(trim($gstRate, '%'));
                                                $formattedGstRate = number_format($rateValue, 1) . '%';
                                            } else {
                                                $formattedGstRate = '-';
                                            }
                                        @endphp
                                        GST {{ $formattedGstRate }}
                                    </strong>
                                </p>
                            </div>
                        


                            <div class="col-lg-3 pb-5">
                                Tax Preference
                                <p class="mb-0 font-16 pt-2">
                                    <strong>
                                        @php
                                            $firstGstTaxability = $stockItemVoucherItem->pluck('gst_taxability')->filter()->first();
                                        @endphp {{ $firstGstTaxability ?? '-' }}
                                    </strong>
                                </p>
                            </div>
                            <div class="col-lg-3 pb-5">
                                
                            </div>
                        </div>
                   

                </div>
            </div>
        </div>
    </div>
{{-- @endif --}}
