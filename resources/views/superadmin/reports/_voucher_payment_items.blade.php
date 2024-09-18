@extends("layouts.main")
@section('title', __('Reports | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .table-responsive-scroll {
        max-height: 500px; /* Set to your preferred height */
        overflow-y: auto;
        overflow-x: hidden !important; /* Optional, hides horizontal scrollbar */
        border: 1px solid #ddd;
    }
    .voucher-details {
        display: flex;
        flex-direction: column;
        margin-left: 0.5rem;
    }

    .voucher-number, .voucher-type {
        display: block;
    }

</style>
{{-- @if ($saleReceiptItem)
    <!-- Code to display data related to $saleReceiptItem -->
@else
    <div class="alert alert-warning">
        No receipt item found for the selected voucher.
    </div>
@endif --}}

@endsection
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Reports</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Voucher Item</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;" class="btn btn-primary compose-mail-btn" onclick="history.back();"><i class='bx bx-left-arrow-alt me-2'></i> Voucher Item</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.VoucherItemPayment', ['VoucherItem' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('VoucherItem') == $item->id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                    <div class="voucher-details">
                                        <div class="voucher-number">{{ $item->voucher_number }}</div>
                                        <div class="voucher-type font-10">{{ $item->voucher_type }} | {{ \Carbon\Carbon::parse($item->voucher_date)->format('j F Y') }}</div>
                                    </div>
                                    @if(isset($item->badge))
                                        <span class="badge bg-primary rounded-pill ms-auto">{{ $item->badge }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="email-header d-xl-flex align-items-center padding-0" style="height: auto;">
                <div class="d-flex align-items-center">
                    <div class="">
                        <h4 class="my-1 text-info">{{ $voucherItem->party_ledger_name }} | {{ $voucherItem->voucher_type }}</h4>
                    </div>
                </div>
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                       
                        <div class="col-lg-12">
                            <div class="col">
                                <div class="card radius-10 border-start border-0 border-4 border-info">
                                    <div class="card-body">
                                        <div class="row p-2">
                                            <div class="col-lg-10" style="padding: 25px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                                <div class="row">
                                                    <div class="col-lg-2">
                                                        <p class="mb-0 font-13">Issued Date</p>
                                                        <h6>{{ \Carbon\Carbon::parse($voucherItem->voucher_date)->format('j F Y') }}</h6>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <p class="mb-0 font-13"> Amount</p>
                                                        <h6>
                                                            @php
                                                                $filteredVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($voucherItem) {
                                                                    return $voucherHead->ledger_name === $voucherItem->party_ledger_name;
                                                                });
                                                            @endphp

                                                            @foreach($filteredVoucherHeads as $gstVoucherHead)
                                                                {{ number_format(abs($gstVoucherHead->amount), 2) }}
                                                            @endforeach
                                                        </h6>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <p class="mb-0 font-13">Pending Amount</p>
                                                        <h6 id="totalPendingAmount"></h6>
                                                    </div>
                                                    @foreach($successfulAllocations as $allocation)
                                                            @foreach($allocation['bank_allocations'] as $bankAllocation)
                                                                    <div class="col-lg-3">
                                                                        <p class="mb-0 font-13">Mode of payment</p>
                                                                        <h6>{{ $bankAllocation->transaction_type }}</h6>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <p class="mb-0 font-13">Account</p>
                                                                        <h6>{{ $allocation['voucher_head']->ledger_name }}</h6>
                                                                    </div>
                                                            @endforeach
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-lg-2" style="padding: 25px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                                <div class="col-lg-12">
                                                            <p class="mb-0 font-13">Status</p>
                                                            <h6 id="statusText" class="text-info"></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 px-2">
                            <div class="col">
                                <div class="accordion" id="accordionExample">
                                    <input type="hidden" id="totalCreditAmount" value="{{ $pendingVoucherHeads->where('entry_type', 'credit')->sum('amount') }}">
                                    <input type="hidden" id="totalDebitAmount" value="{{ $pendingVoucherHeads->where('entry_type', 'debit')->sum('amount') }}">
                                    @include('superadmin.reports.accordion._accordion_item_one')
                                    @include('superadmin.reports.accordion._accordion_item_two')
                                    @include('superadmin.reports.accordion._accordion_item_seven')
                                    @include('superadmin.reports.accordion._accordion_item_four')
                                    @include('superadmin.reports.accordion._accordion_item_five')
                                    @include('superadmin.reports.accordion._accordion_item_three')
                                    
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!--end email wrapper-->           
    </div>
</div>
@endsection
@push('css')
@include('layouts.includes.datatable-css')
@endpush
@push('javascript')
<script>
	new PerfectScrollbar('.email-navigation');
	new PerfectScrollbar('.email-list');
</script>
@include('layouts.includes.datatable-js')
<script>
 document.addEventListener('DOMContentLoaded', function() {
    console.log('Script running...'); // To check if the script is running

    // Get the values for credit and debit amounts and apply Math.abs to get the absolute value
    const totalCreditAmount = Math.abs(parseFloat(document.getElementById('totalCreditAmount').value)) || 0;
    const totalDebitAmount = Math.abs(parseFloat(document.getElementById('totalDebitAmount').value)) || 0;
    
    // Calculate the total pending amount
    const totalPendingAmount = totalCreditAmount - totalDebitAmount;

    // Format the pending amount with rupee sign
    const formattedPendingAmount = `₹${Math.abs(totalPendingAmount).toFixed(2)}`;
    
    // Display the formatted total pending amount
    document.getElementById('totalPendingAmount').innerText = formattedPendingAmount;

    // Determine and display the status
    const statusElement = document.getElementById('statusText'); // Use the ID selector
    if (totalPendingAmount === 0) {
        statusElement.innerText = 'Settled';
    } else {
        statusElement.innerText = 'Partially Settled';
    }

    console.log('Total Pending Amount:', totalPendingAmount); // Debugging info
    console.log('Status Text:', statusElement.innerText); // Debugging info
});


</script>



<script>
    $(document).ready(function() {
        $('#sale-item-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: '{{ route('reports.VoucherItem.data', $voucherItemId) }}',
            columns: [
                { data: 'stock_item_name',name: 'stock_item_name'},
                { data: 'gst_hsn_name', name: 'gst_hsn_name' },
                { data: 'billed_qty', name: 'billed_qty' },
                { 
                    data: 'rate', name: 'rate', className: 'text-center',
                    render: function(data, type, row) {
                        return data + '/' + row.unit;  
                    }
                },
                { 
                    data: 'igst_rate', name: 'igst_rate',
                    render: function(data, type, row) {
                        return data ? data + '%' : '-';
                    }
                },
                { 
                    data: 'discount', name: 'discount',
                    render: function(data, type, row) {
                        return data ? data + '%' : '-';
                    }
                },
                {
                    data: 'amount', name: 'amount', className: 'text-end',
                    render: function(data, type, row) {
                        return data ? parseFloat(Math.abs(data)).toFixed(2) : '0.00';
                    }
                }

            ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                      // Helper function to parse and clean values
                        var intVal = function(i) {
                            return typeof i === 'string' ? i.replace(/[\₹,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };

                        var formatValue = function(value) {
                            return value === 0 ? '0.00' : Math.abs(value).toFixed(2);
                        };

                    var subtotal = api.column(6, { page: 'all' }) .data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                    $('#subtotal').text(Math.abs(subtotal).toFixed(2));
                    
                    
                    var gstVoucherHeadAmount = 0;
                    $('[data-amount]').each(function() {
                        var amount = parseFloat($(this).attr('data-amount')) || 0;
                        gstVoucherHeadAmount += amount;
                    });

                    // Calculate Total Invoice Value
                    var totalInvoiceValue = subtotal + gstVoucherHeadAmount;

                    $('#totalInvoiceValue').text(Math.abs(totalInvoiceValue).toFixed(2));
                    $('#totalInvoiceAmount').text(Math.abs(totalInvoiceValue).toFixed(2));
                    $('#totalLedgerAmount').text(Math.abs(totalInvoiceValue).toFixed(2));
                    $('#totalPaymentInvoiceAmount').text(Math.abs(totalInvoiceValue).toFixed(2));

              
                    $('#pendingDue').text(new Intl.NumberFormat('en-IN').format(pendingDue));
                    $('#VoucherHeadDebitAmount').text(new Intl.NumberFormat('en-IN').format(VoucherHeadDebitAmount));
                }
        });
    });
</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>
@endsection