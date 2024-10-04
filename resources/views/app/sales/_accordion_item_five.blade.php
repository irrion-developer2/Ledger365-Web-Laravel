<div class="accordion-item">
    <h2 class="accordion-header" id="headingFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
            <i class="bx bx-link fs-4"></i>&nbsp; Linked Document  <p class="" style="margin-left: auto;">{{ $totalCountLinkHeads }} Document</p>
        </button>
    </h2>
    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            <div class="table-responsive table-responsive-scroll  border-0">
                <table class="table table-striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <td>Date</td>
                            <td>Doc. No.</td>
                            <td>Type</td>
                            <td>&#8377 Amount</td>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- @dd($saleReceiptItem); --}}
                        @if ($voucherHeadsSaleReceipt->count() > 0)
                            @if ($voucherHeadsSaleReceipt->isNotEmpty())
                                @foreach ($voucherHeadsSaleReceipt as $voucherHead)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($saleReceiptItem->voucher_date)->format('j F Y') }}</td>
                                        <td>{{ $saleReceiptItem->voucher_number }}</td>
                                        <td>{{ $saleReceiptItem->voucher_type }}</td>
                                        <td>₹{{ number_format(abs($voucherHead->amount), 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4">No credit entries found for this voucher.</td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td colspan="4">No specific sale voucher found for the specified ID.</td>
                            </tr>
                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>