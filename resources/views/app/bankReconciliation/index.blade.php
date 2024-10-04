@extends("layouts.main")
@section('title', __('Bank Reconciliation | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="assets/plugins/fancy-file-uploader/fancy_fileupload.css" rel="stylesheet" />
	<link href="assets/plugins/Drag-And-Drop/dist/imageuploadify.min.css" rel="stylesheet" />
    <style>
        .imageuploadify .imageuploadify-images-list .imageuploadify-container .imageuploadify-details {
            opacity: 1;
            color: #000000;
        }
    </style>
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2 p-0">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Bank Reconciliation</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Bank Reconciliation</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-2">

                    <form action="/upload-pdf" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="pdf_file" required>
                        <button type="submit">Upload PDF</button>
                    </form>

                    
                    <button type="button" class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#importModal">Import</button>
                    

                    <!-- Modal -->
                    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('BankReconciliation.import') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="importModalLabel">Upload Banking</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input id="image-uploadify" name="pdf" type="file" accept=".xlsx,.xls,image/*,.doc,audio/*,.docx,video/*,.ppt,.pptx,.txt,.pdf" multiple="" style="display: none;">
                     
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Upload</button>
                                    </div> 
                                </form>
                            </div>
                        </div>
                    </div>        
                    <!-- Modal -->       

                    <div class="table-responsive table-responsive-scroll border-0">
                        
                        <table id="bankReconciliation-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Chq./Ref.No.</th>
                                    <th>Withdrawl</th>
                                    <th>Deposit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Chq./Ref.No.</th>
                                    <th>Withdrawl</th>
                                    <th>Deposit</th>
                                    <th>Balance</th>
                                </tr>
                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("script")
@include('layouts.includes.datatable-js-css')
<script>
    $('#fancy-file-upload').FancyFileUpload({
        params: {
            action: 'fileuploader'
        },
        maxfilesize: 1000000
    });
</script>
<script>
    $(document).ready(function () {
        $('#image-uploadify').imageuploadify();
    })
</script>

<script src="assets/plugins/fancy-file-uploader/jquery.ui.widget.js"></script>
<script src="assets/plugins/fancy-file-uploader/jquery.fileupload.js"></script>
<script src="assets/plugins/fancy-file-uploader/jquery.iframe-transport.js"></script>
<script src="assets/plugins/fancy-file-uploader/jquery.fancy-fileupload.js"></script>
<script src="assets/plugins/Drag-And-Drop/dist/imageuploadify.min.js"></script>


<script>
    $(document).ready(function() {
        var table = new DataTable('#bankReconciliation-datatable', {
            fixedColumns: {
                start: 1,
            },
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('BankReconciliation.get-data') }}",
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                { 
                    data: null,
                    name: 'row_index',
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {data: 'transaction_date', name: 'transaction_date'},
                {data: 'narration', name: 'narration', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'transaction_type', name: 'transaction_type'},
                {data: 'chq_ref_no', name: 'chq_ref_no', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'withdrawal', name: 'withdrawl', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'deposit', name: 'deposit', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'balance', name: 'balance', render: function(data, type, row) {
                    return data ? data : '-';
                }},
               
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var withdrawlToTotal = 5;
                var depositToTotal = 6;
                var balanceToTotal = 7;

                var withdrawltotal = api.column(withdrawlToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var deposittotal = api.column(depositToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);
                var balancetotal = api.column(balanceToTotal).data().reduce(function (a, b) {
                    return (parseFloat(sanitizeNumber(a)) || 0) + (parseFloat(sanitizeNumber(b)) || 0);
                }, 0);

                $(api.column(withdrawlToTotal).footer()).html(number_format(Math.abs(withdrawltotal), 2));
                $(api.column(depositToTotal).footer()).html(number_format(Math.abs(deposittotal), 2));
                $(api.column(balanceToTotal).footer()).html(number_format(Math.abs(balancetotal), 2));
            },
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

        function number_format(number, decimals) {
            if (isNaN(number)) return 0;
            number = parseFloat(number).toFixed(decimals);
            var parts = number.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }
    });
</script>
@endsection
