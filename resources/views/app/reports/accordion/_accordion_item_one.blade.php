<div class="accordion-item">
    <h2 class="accordion-header" id="headingOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            <i class="bx bx-group fs-4"></i>&nbsp; Party Details
            @if($voucherItem->party_ledger_name)
                <p class="" style="margin-left: auto;">Bill to: {{ $voucherItem->party_ledger_name }}</p>
            @endif
        </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            @if($voucherItem->party_ledger_name)
                <p class="mb-0 font-16">Bill to</p>
                <strong class="mb-0 font-16">{{ $voucherItem->party_ledger_name }}</strong>
            @endif
            @foreach($ledgerData as $data)
                @if($data->gst_in)
                    <p class="mb-0 font-16">GSTIN: {{ $data->gst_in }}</p>
                @endif
                @if($data->address)
                    <p class="mb-0 font-16">{{ $data->address }}</p>
                @endif
                @if($data->state)
                    <p class="mb-0 font-16">{{ $data->state }}</p>
                @endif
            @endforeach
            @if($voucherItem->place_of_supply)
                <p class="mb-0 font-16">Place of supply: {{ $voucherItem->place_of_supply }}</p>
            @endif
            @if($data->email)
                <p class="mb-0 font-16">Email: {{ $data->email }}</p>
            @endif
        </div>
    </div>
</div>
