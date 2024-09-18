
<div class="accordion-item">
    <h2 class="accordion-header" id="headingThree">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
           <i class="bx bx-book-open fs-4"></i>&nbsp; Accounting Details <p class="" style="margin-left: auto;">{{ $totalCountHeads }} A/c's impacted</p>
        </button>
    </h2>
    <div id="collapseThree" class="accordion-collapse collapse show" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
        <div class="accordion-body">	
            <div class="table-responsive table-responsive-scroll  border-0">
                <table class="table table-striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <td>Account Name</td>
                            <td>&#8377 Credit</td>
                            <td>&#8377 Debit</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucherHeads as $voucherHead)
                            <tr>
                                <th>{{ $voucherHead->ledger_name }}</th>
                                @if($voucherHead->entry_type == 'credit')
                                    <th credit-amount="{{ $voucherHead->amount }}">{{ number_format(abs($voucherHead->amount), 2) }}</th>
                                    <th></th>
                                @elseif($voucherHead->entry_type == 'debit')
                                    <th></th>
                                    <th debit-amount="{{ $voucherHead->amount }}">{{ number_format(abs($voucherHead->amount), 2) }}</th>
                                @else
                                    <th></th>
                                    <th></th>
                                @endif
                            </tr>
                        @endforeach 
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>