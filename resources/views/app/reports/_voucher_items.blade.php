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
    table, th, td {
        border: 1px solid grey !important;
        border-collapse: collapse;
        font-size: 10px;
    }
    #pay-now-button {
        border: none;
        background: none !important;
        font-size: 20px;
    }
    .nav-primary.nav-tabs .nav-link.active {
        color: #0d6efd;
        border-color: #fff #fff #fff !important;
        border-bottom: 1px solid !important;
    }
</style>
@endsection
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content p-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Reports</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Voucher view </li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">

            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> <a href="javascript:;" class="btn btn-primary compose-mail-btn" onclick="history.back();"><i class='bx bx-left-arrow-alt me-2'></i> Voucher view</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('reports.VoucherItem', ['VoucherItem' => $item->voucher_id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('VoucherItem') == $item->voucher_id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                    <div class="voucher-details">
                                        <div class="voucher-number">{{ $item->voucher_number }}</div>
                                        <div class="voucher-type font-10">{{ $item->voucher_type_name }} | {{ \Carbon\Carbon::parse($item->voucher_date)->format('j F Y') }}</div>
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

           


            <div class="email-header d-xl-flex align-items-center">

                

                <div class="d-flex align-items-center">
                    <h4 class="my-1 text-info">
                        {{ $voucherItemName->first()->ledger_name ?? 'N/A' }} | {{ $voucherItem->voucher_type_name }}
                    </h4>
                </div>

                <div class="ms-auto d-flex align-items-center">
                    
                        <ul class="nav nav-tabs nav-primary" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#voucherType" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-title">{{ $voucherItem->voucher_type_name }}</div>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#paymentReceived" role="tab" aria-selected="false" tabindex="-1">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-title">Payment Received</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                  
                </div>

                <div class="ms-auto d-flex align-items-center" id="pay-section-wrapper">
                    <button id="pay-now-button" onclick="printInvoice()"><i class="bx bx-printer"></i></button>
                </div>
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">



                       
                        <div class="col-lg-12">
                            <div class="col">
                                <div class="card radius-10 border-start border-0 border-4 border-info">
                                    <div class="card-body p-1">

                                        @if($voucherItem->voucher_type_name == 'Receipt')
                                            <div class="row p-2">
                                                <div class="col-lg-10" style="padding: 12px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                                    <div class="row">
                                                        <div class="col-lg-2">
                                                            <p class="mb-0 font-13">Issued Date</p>
                                                            <h6>{{ \Carbon\Carbon::parse($voucherItem->voucher_date)->format('j F Y') }}</h6>
                                                        </div>
                                                        <div class="col-lg-2">
                                                            <p class="mb-0 font-13">Amount</p>
                                                            <h6>
                                                                @foreach($gstVoucherHeads as $gstVoucherHead)
                                                                    ₹{{ indian_format(abs($gstVoucherHead->amount)) }}
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
                                                <div class="col-lg-2" style="padding: 12px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                                    <div class="col-lg-12">
                                                        <p class="mb-0 font-13">Status</p>
                                                        <h6 id="statusText" class="text-info"></h6>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="row p-2">
                                                <div class="col-lg-9" style="padding: 12px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                                    <div class="row">
                                                        <div class="col-lg-3">
                                                            <p class="mb-0 font-13">Issued Date</p>
                                                            <h6>{{ \Carbon\Carbon::parse($voucherItem->voucher_date)->format('j F Y') }}</h6>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <p class="mb-0 font-13">Amount</p>
                                                            <h6>
                                                                @foreach($gstVoucherHeads as $gstVoucherHead)
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
                                                <div class="col-lg-3" style="padding: 12px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                                    <div class="col-lg-12">
                                                        <p class="mb-0 font-13">Status</p>
                                                        <h6 id="statusText" class=""></h6>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif


                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 px-2">
                            <div class="col">
                                <!--end breadcrumb-->
                            
			
                                <input type="hidden" id="totalCreditAmount" value="{{ $pendingVoucherHeads->where('entry_type', 'credit')->sum('amount') }}">
                                <input type="hidden" id="totalDebitAmount" value="{{ $pendingVoucherHeads->where('entry_type', 'debit')->sum('amount') }}">
                                


                                <div class="tab-content py-3">
                                    <div class="tab-pane fade show active" id="voucherType" role="tabpanel">
                                        @if(!in_array($voucherItem->voucher_type_name, ['Sales', 'Purchase', 'Credit Note', 'Debit Note']))
                                            @include('app.reports.partials._invoiceT')
                                        @else
                                            @include('app.reports.partials._invoice')
                                        @endif
                                    </div>
                                    <div class="tab-pane fade" id="paymentReceived" role="tabpanel">
                                        @include('app.reports.partials._paymentReceived')
                                        {{-- @include('app.reports.accordion._accordion_item_five') --}}
                                    </div>
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
        @php
            $specificAmount = $voucherHeads->sum('amount');
        @endphp
        const givenAmount = parseFloat(`{{ number_format(abs($specificAmount), 2) }}`.replace(/,/g, ''));
        
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
            searching: false,
            dom: '<"top"f>rt<"bottom"lp><"clear">', 
            ajax: '{{ route('reports.VoucherItem.data', $voucherItemId) }}',
            columns: [
                { 
                    data: null, 
                    name: 'row_index',
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        return meta.row + 1;  // Display the row index (starting from 1)
                    }
                },
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

                    var subtotal = api.column(7, { page: 'all' }) .data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                    $('#subtotal').text(Math.abs(subtotal).toFixed(2));
                    
                    
                    var gstVoucherHeadAmount = 0;
                    $('[data-amount]').each(function() {
                        var amount = parseFloat($(this).attr('data-amount')) || 0;
                        gstVoucherHeadAmount += amount;
                    });

                    var totalInvoiceValue = subtotal + gstVoucherHeadAmount;

                    var totalInvoiceValueWord = numberToWords(totalInvoiceValue);
                    $('#totalInvoiceValueWord').text(totalInvoiceValueWord);


                    $('#totalInvoiceValue').text(Math.abs(totalInvoiceValue).toFixed(2));
                    $('#totalPaymentInvoiceAmount').text(Math.abs(totalInvoiceValue).toFixed(2));
                }
        });
    });

    function numberToWords(number) {
        const ones = [
            'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        const tens = [
            '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'
        ];
        const thousands = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

        function convertHundreds(number) {
                    let result = '';
                    if (number < 20) {
                        result = ones[number];
                    } else if (number < 100) {
                        result = tens[Math.floor(number / 10)] + (number % 10 ? ' ' + ones[number % 10] : '');
                    } else {
                        result = ones[Math.floor(number / 100)] + ' Hundred' + (number % 100 ? ' and ' + convertHundreds(number % 100) : '');
                    }
                    return result;
        }

        if (number === 0) {
            return 'Zero';
        }

        let result = '';
        let i = 0;
        while (number > 0) {
            if (number % 1000 !== 0) {
                result = convertHundreds(number % 1000) + ' ' + thousands[i] + (result ? ' ' + result : '');
            }
            number = Math.floor(number / 1000);
            i++;
        }

        return result.trim();
    }

</script>
<script>
    $(document).ready(function() {
        $('#hsnsac-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: '<"top"f>rt<"bottom"lp><"clear">', 
            ajax: {
                url: '{{ route('reports.VoucherItem.data', $voucherItemId) }}',
                dataSrc: function(json) {
                    // Grouping data by `gst_hsn_name` and summing the `amount`
                    var groupedData = {};
    
                    json.data.forEach(function(item) {
                        if (groupedData[item.gst_hsn_name]) {
                            groupedData[item.gst_hsn_name] += parseFloat(item.amount);
                        } else {
                            groupedData[item.gst_hsn_name] = parseFloat(item.amount);
                        }
                    });
    
                    // Convert grouped data back into an array format for DataTables
                    var finalData = Object.keys(groupedData).map(function(key) {
                        return {
                            gst_hsn_name: key,
                            amount: groupedData[key]
                        };
                    });
    
                    return finalData;
                }
            },
            columns: [
                { data: 'gst_hsn_name', name: 'gst_hsn_name' },
                {
                    data: 'amount', 
                    name: 'amount', 
                    className: 'text-end',
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
    
                var hsnsacSubtotal = api.column(1, { page: 'all' }).data().reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
    
                $('#hsnsacSubtotal').text(Math.abs(hsnsacSubtotal).toFixed(2));
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#gst-table').DataTable({
            processing: true,
            serverSide: true,
            paging: false,
            searching: false,
            dom: '<"top"f>rt<"bottom"lp><"clear">', 
            ajax: '{{ route('reports.VoucherItemTax.data', $voucherItemId) }}',
            columns: [
                { data: 'ledger_name', name: 'ledger_name' },
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

                var taxSubtotal = api.column(1, { page: 'all' }) .data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                $('#taxSubtotal').text(Math.abs(taxSubtotal).toFixed(2));
            }
        });
    });
</script>
@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>
@endsection