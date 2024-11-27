@php
    $hasDetails = $saleItem->narration || $saleItem->reference_no || $saleItem->reference_date;
@endphp

@if($hasDetails)
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingFour">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                <i class="bx bx-file fs-4"></i>&nbsp; Additional Details 
            </button>
        </h2>
        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
            <div class="accordion-body">
                <div class="col-lg-12">
                    @if($saleItem->narration)
                        <div class="row">
                            <div class="col-lg-3">
                                Narration
                            </div>
                            <div class="col-lg-3">
                                <p class="mb-0 font-16">: {{ $saleItem->narration }}</p>
                            </div>
                        </div>
                    @endif
                    @if($saleItem->reference_no)
                        <div class="row">
                            <div class="col-lg-3">
                                Reference No.
                            </div>
                            <div class="col-lg-3">
                                <p class="mb-0 font-16">: {{ $saleItem->reference_no }}</p>
                            </div>
                        </div>
                    @endif
                    @if($saleItem->reference_date)
                        <div class="row">
                            <div class="col-lg-3">
                                Reference Date
                            </div>
                            <div class="col-lg-3">
                                <p class="mb-0 font-16">: {{ \Carbon\Carbon::parse($saleItem->reference_date)->format('j F Y') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
