@php
    // Check if successfulAllocations is an array and if it's not empty
    $hasAllocations = is_array($successfulAllocations) && count($successfulAllocations) > 0;
    $hasBankAllocations = false;

    if ($hasAllocations) {
        foreach ($successfulAllocations as $allocation) {
            if (isset($allocation['bank_allocations']) && !empty($allocation['bank_allocations'])) {
                $hasBankAllocations = true;
                break;
            }
        }
    }
@endphp

@if($hasAllocations && $hasBankAllocations)
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingSeven">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="true" aria-controls="collapseSeven">
                <i class="bx bx-log-in fs-4"></i>&nbsp; Payment Details 
                    <p class="" style="margin-left: auto;">Others</p>
            </button>
        </h2>
        <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionExample">
            <div class="accordion-body">
                <div class="col-lg-12">
                    @foreach($successfulAllocations as $allocation)
                        @if(isset($allocation['bank_allocations']) && !empty($allocation['bank_allocations']))
                            @foreach($allocation['bank_allocations'] as $bankAllocation)
                                @if($bankAllocation)
                                    <p class="mb-0 font-16"><b>{{ \Carbon\Carbon::parse($bankAllocation->instrument_date)->format('j F Y') }}</b></p>
                                    @if($bankAllocation->instrument_number)
                                        <div class="row">
                                            <div class="col-lg-3">
                                                Instrument No.
                                            </div>
                                            <div class="col-lg-3">
                                                <p class="mb-0 font-16">: {{ $bankAllocation->instrument_number }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if($bankAllocation->bank_name)
                                        <div class="row">
                                            <div class="col-lg-3">
                                                Bank Name
                                            </div>
                                            <div class="col-lg-3">
                                                <p class="mb-0 font-16">: {{ $bankAllocation->bank_name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if($bankAllocation->transaction_type)
                                        <div class="row">
                                            <div class="col-lg-3">
                                                Mode
                                            </div>
                                            <div class="col-lg-3">
                                                <p class="mb-0 font-16">: {{ $bankAllocation->transaction_type }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if($bankAllocation->amount)
                                        <div class="row">
                                            <div class="col-lg-3">
                                                Amount Paid
                                            </div>
                                            <div class="col-lg-3">
                                                <p class="mb-0 font-16">: ₹{{ number_format(abs($bankAllocation->amount), 2) }}</p>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
