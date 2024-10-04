@extends("layouts.main")
@section('title', __('Invoices | PreciseCA'))
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
@endsection
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content p-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Invoices</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Sales</li>
                        
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> 
                    <a href="javascript:;" class="btn btn-primary compose-mail-btn" onclick="history.back();"><i class='bx bx-left-arrow-alt me-2'></i> Sales</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('sales.items', ['SaleItem' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('SaleItem') == $item->id ? 'active' : '' }}" style="border-top: none;">
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
                        <h4 class="my-1 text-info">{{ $saleItem->party_ledger_name }} | {{ $saleItem->voucher_type }}</h4>
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
                                            <div class="col-lg-9" style="padding: 25px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <p class="mb-0 font-13">Issued Date</p>
                                                        <h6>{{ \Carbon\Carbon::parse($saleItem->voucher_date)->format('j F Y') }}</h6>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <p class="mb-0 font-13">Amount</p>
                                                        <h6>
                                                            @php
                                                                $filteredVoucherHeads = $voucherHeads->filter(function ($voucherHead) use ($saleItem) {
                                                                    return $voucherHead->ledger_name === $saleItem->party_ledger_name;
                                                                });
                                                            @endphp

                                                            @foreach($filteredVoucherHeads as $gstVoucherHead)
                                                                    ₹{{ number_format(abs($gstVoucherHead->amount), 2) }}
                                                            @endforeach
                                                        </h6>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <p class="mb-0 font-13">Pending Amount</p>
                                                        <h6 id="totalPendingAmount"></h6>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <p class="mb-0 font-13">Due Date</p>
                                                        <h6>{{ \Carbon\Carbon::parse($dueDate)->format('j F Y') }}</h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3" style="padding: 25px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                                <div class="col-lg-12">
                                                            <p class="mb-0 font-13">Status</p>
                                                            <h6 id="statusText" class=""></h6>
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
                                    
                                    @include('app.sales._accordion_item_one')
                                    @include('app.sales._accordion_item_two')
                                    @include('app.sales._accordion_item_six')
                                    @include('app.sales._accordion_item_four')
                                    @include('app.sales._accordion_item_five')
                                    @include('app.sales._accordion_item_three')
                                    
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
        // Get total credit and debit amounts
        const totalCreditAmount = Math.abs(parseFloat(document.getElementById('totalCreditAmount').value)) || 0;
        const totalDebitAmount = Math.abs(parseFloat(document.getElementById('totalDebitAmount').value)) || 0;
        
        // Calculate total pending amount
        const totalPendingAmount = totalCreditAmount - totalDebitAmount;
        const formattedPendingAmount = `₹${Math.abs(totalPendingAmount).toFixed(2)}`;
        document.getElementById('totalPendingAmount').innerText = formattedPendingAmount;

        // Get the given amount from Blade template
        const givenAmount = parseFloat(`{{ number_format(abs($gstVoucherHead->amount), 2) }}`.replace(/,/g, ''));
        
        console.log('Given Amount:', givenAmount);
        console.log('Total Pending Amount:', totalPendingAmount);

        // Set status based on totalPendingAmount
        const statusElement = document.getElementById('statusText');

        if (Math.abs(totalPendingAmount) === 0) {
            statusElement.innerText = 'PAID';
            statusElement.style.color = 'green'; // Optional: Set color for PAID status
        } else if (Math.abs(totalPendingAmount) === givenAmount) {
            statusElement.innerText = 'UNPAID';
            statusElement.style.color = 'red'; // Set color for UNPAID status
        } else {
            statusElement.innerText = 'PARTIALLY PAID';
            statusElement.style.color = 'orange'; // Optional: Set color for PARTIALLY PAID status
        }
    });
</script>
<script>
    $(document).ready(function() {
        $('#sale-item-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            ajax: '{{ route('sales.SaleItem.data', $saleItemId) }}',
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
                        return typeof i === 'string' ?
                            i.replace(/[\₹,]/g, '') * 1 :
                            typeof i === 'number' ?
                                i : 0;
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

                    // Calculate and Update Pending Due Amount
                    var creditAmountText = $('[id$="creditAmount"]').text();
                    var creditAmount = parseFloat(creditAmountText.replace(/[\₹,]/g, '')) || 0;

                    var totalPaymentInvoiceAmount = parseFloat($('#totalPaymentInvoiceAmount').text().replace(/[\₹,]/g, '')) || 0;
                    var pendingDue = totalPaymentInvoiceAmount - creditAmount;


                    $('#pendingDue').text(new Intl.NumberFormat('en-IN').format(pendingDue));
                }
        });
    });
</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>
@endsection