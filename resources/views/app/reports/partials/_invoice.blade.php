
        <style>
            html, body {
                margin: 0;
                padding: 0;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size:12px;
            }
            h3 {
                text-align: center;
                margin-bottom: 5px;
            }
            table, th, td {
                border: 1px solid grey !important;
                border-collapse: collapse;
                font-size: 10px;
            }
            th, td {
                padding: 5px;
                text-align: left;    
            }
            /* table {
                width: 100%;
                border-collapse: collapse;
            }
            table, th, td {
                border: 1px solid black;
                padding: 5px;
            } */
            .noBorder {
                border-top: none;
                border-bottom: none;
            }
            .irn {
                max-width: 200px;
            }
            .wordBreak {
                width: 110px;
                word-break: break-all;
                margin: auto;
            }
            .textRight {
                text-align: right;
            }
            .bold {
                font-weight: 600;
            }
            .headHSN {
                text-align: center;
            }
            #pay-section-wrapper {
                display: flex;
                justify-content: center;
                margin: 15px 0;
            }
            #pay-now-button {
                border: none;
                background: none !important;
                /* background-color: #2b3b95; */
                /* color: white; */
                /* padding: 10px 60px; */
                /* border-radius: 4px; */
                /* text-decoration: none; */
                font-size: 20px;
                /* font-weight: 600; */
            }
            #narrationContainer div {
                max-width: 600px;
                overflow-wrap: break-word;
                white-space: pre-wrap;
            }
            .lastDeclaration{
                margin-bottom: 100px;
            }
            @@media print {
                table, th, td {
                    border: 1px solid grey !important;
                }
                body {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                }
                h3 {
                    text-align: center;
                    margin-bottom: 5px;
                }
                .noPrint {
                    display: none;
                }
                table {
                    page-break-inside: avoid;
                }
                tr, td {
                    page-break-inside: avoid;
                }
                #invoiceContent {
                    margin: 0;
                    padding: 0;
                }
                table, th, td {
                    border: 1px solid grey !important;
                    border-collapse: collapse;
                    font-size: 10px;
                }
                @page {
                    size: auto;
                    margin: 0;
                }
            }

        </style>
   
<!-- Print Button -->
{{-- <div id="pay-section-wrapper">
    <button id="pay-now-button" onclick="printInvoice()">Print</button>
</div> --}}


<div id="invoiceContent">

        <h3>Tax Invoice</h3>

        <table style="width: 100%;">
            <tr>
                <td colspan='2' rowspan='4'>
                    <table style='border:0'>
                        @foreach($companies as $company)
                            <tr>
                                <b>{{ $company->name }}</b><br>
                                {{ $company->state }}
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td colspan='3'>
                    <div style='display: flex; justify-content: space-between;'>
                        <div>Invoice No.<br><b>{{ $voucherItem->voucher_number }}</b></div>
                        <div>e-Way Bill No.<br><b></b></div>
                    </div>
                </td>
                <td colspan='3'>
                    Dated<br><b>{{ \Carbon\Carbon::parse($voucherItem->voucher_date)->format('j F Y') }}</b>
                </td>
            </tr>
            <tr></tr>
            <tr>
                <td colspan='3'>Delivery Note<br>
                    <b> {{ $voucherItem->delivery_notes }}</b>
                </td>
                <td colspan='3'>Mode/Terms of Payments<br><b>{{ $voucherItem->due_date_payment }} </b></td>
            </tr>
            <tr>
                <td colspan='3'>Reference No. & Date.<br><b>{{ $voucherItem->reference_no }}</b>
                    dt. <b>{{ \Carbon\Carbon::parse($voucherItem->reference_date)->format('j F Y') }}</b>
                </td>
                <td colspan='3'>Other Reference(s)<br>
                    <b> {{ $voucherItem->order_ref }}</b>
                </td>
            </tr>
            <tr>
                <td colspan='2' rowspan='3'>
                    Consignee<br>
                            <b>{{ $voucherItem->consignee_name }}</b><br>
                            GSTIN/UIN: {{ $voucherItem->consignee_gstin }}<br>
                            {{ $voucherItem->consignee_addr }}<br>
                            State: {{ $voucherItem->consignee_state_name }}
                </td>
                <td colspan='3'>Buyer's Order No.<br>
                    <b>{{ $voucherItem->order_no }}</b>
                </td>
                <td colspan='3'>Dated<br>
                    <b>{{ \Carbon\Carbon::parse($voucherItem->order_date)->format('j F Y') }}</b>
                </td>
            </tr>
            <tr>
                <td colspan='3'>Dispatch Document No.<br>
                    <b>{{ $voucherItem->ship_doc_no }}</b>
                </td>
                <td colspan='3'>Delivery Note Date<br>
                    <b>{{ $voucherItem->delivery_dates }}</b>
                </td>
            </tr>
            <tr>
                <td colspan='3'>Despatched through<br>
                    <b>{{ $voucherItem->ship_by }}</b>
                </td>
                <td colspan='3'>Destination<br>
                    <b>{{ $voucherItem->final_destination }}</b>
                </td>
            </tr>
            <tr>
                <td colspan='2' rowspan='4'>
                    Buyer<br>
                    <b>{{ $voucherItem->party_ledger_name }}</b><br>
                            GSTIN/UIN: {{ $voucherItem->buyer_gstin }}<br>
                            {{ $voucherItem->buyer_addr }}<br>
                            {{-- State: {{ $voucherItem->state }} --}}
                </td>
                <td colspan='3'>Bill of Landing/LR-RR No.<br>
                    <b> {{ $voucherItem->bill_lading_no }}</b> dt. <b>{{ \Carbon\Carbon::parse($voucherItem->bill_lading_date)->format('j F Y') }}</b>
                </td>
                <td colspan='3'>Motor Vehicle<br>
                    <b>{{ $voucherItem->vehicle_no }}</b>
                </td>
            </tr>
            <tr>
                <td colspan='6' rowspan='3'>
                    <b>Terms of Delivery:</b><br>
                    <b>{{ $voucherItem->terms }}</b>
                </td>
            </tr>
        </table>
       

        <table class="table" id="sale-item-table" style="width: 100%;border: none !important;padding:0px;">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Description of Goods</th>
                    <th>HSN/SAC</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>GST</th>
                    <th>Discount</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6"></th>
                    <th style="text-align:right">Subtotal</th>
                    <th style="text-align:right" id="subtotal"></th>
                </tr>
                @foreach($gstVoucherHeads as $gstVoucherHead)
                    <tr>
                        <th colspan="6"></th>
                        <th style="text-align:right">{{ $gstVoucherHead->ledger_name }}</th>
                        <th style="text-align:right" data-amount="{{ $gstVoucherHead->amount }}">{{ number_format(abs($gstVoucherHead->amount), 2) }}</th>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="6"></th>
                    <th style="text-align:right">Total Invoice Value</th>
                    <th style="text-align:right" id="totalInvoiceValue"></th>
                </tr>
                
                <tr>
                    <td colspan='8'>
                        Amount Chargable (in words) 
                        {{-- <i style='margin-left:490px;'>E.&O.E</i> --}}
                        <br>
                        <b>Indian Rupees <span id="totalInvoiceValueWord"></span> only</b>

                    </td>
                </tr>
            </tfoot>
        </table>
        
        <div class="row">
                <div class="col-lg-6 hsnsac">
                    <table class="table" id="hsnsac-table" style="width: 100%;border: none !important;padding:0px;">
                        <thead>
                            <tr>
                                <th width=50%;>HSN/SAC</th>
                                <th width=50%;>Taxable Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table content -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th style="text-align:right" id="hsnsacSubtotal"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
    
                <div class="col-lg-6 gst">
                    <table class="table" id="gst-table" style="width: 100%;border: none !important;padding:0px;">
                        <thead>
                            <tr>
                                <th width=50%;>Tax Rate</th>
                                <th width=50%;>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table content -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Tax Amount</th>
                                <th style="text-align:right" id="taxSubtotal"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
        </div>


        <table class="lastDeclaration"width=100%;>
            <tr>
                <td colspan='4' rowspan='2' width=50%; style=font-size:10px;>
                    <i>Narration</i><br>
                    <div>{{ $voucherItem->narration }}</div><br>
                    <u>Declaration</u><br>
                    We declare that this invoice shows the actual price of 
                    the goods described and that all particulars are true 
                    and correct.
                </td>
            </tr>
            <tr>
                <td colspan='5'>
                    Authorised Signatory<br>
                    @foreach($companies as $company)
                        <b>{{ $company->name }}</b>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td colspan='10' style='text-align: center;'>
                    <small>
                        This is a Computer Generated Invoice<br>
                    </small>
                </td>
            </tr>
        </table>

</div>

    <script>
        function printInvoice() {
            const printContents = document.getElementById('invoiceContent').innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>