<div class="accordion-item">
    <h2 class="accordion-header" id="headingSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="true" aria-controls="collapseSix">
            <i class="bx bx-calendar-week fs-4"></i>&nbsp; Payment Schedule 
        </button>
    </h2>
    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            <div class="table-responsive table-responsive-scroll  border-0">
                <table class="table table-striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <td>Due Date</td>
                            <td>&#8377 Actual Due</td>
                            <td>&#8377credit amount</td>
                            <td>&#8377 Pending Due</td>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($voucherItem)
                        @php
                                $creditAmount = $voucherHeadsSaleReceipt->sum('amount'); 
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($dueDate)->format('j F Y') }}</td>
                                <td id="totalPaymentInvoiceAmount"></td>
                                <td id="creditAmount">{{ number_format($creditAmount, 2) }}</td>
                                <td id="pendingDue"></td>
                            </tr>
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