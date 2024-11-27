@extends("layouts.main")
@section('title', __('Companies | PreciseCA'))

@section("style")
    <link href="https://unpkg.com/vue2-datepicker@3.10.2/index.css" rel="stylesheet">
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Companies</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Companies</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
               

                    <div class="table-responsive table-responsive-scroll border-0">
                        <table id="Companies-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Ledger Name</th>
                                    <th>Company Name</th>
                                    <th>GSTIN</th>
                                    <th>Outstanding</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://unpkg.com/vue2-datepicker@3.10.2/index.min.js"></script>
<script src="{{ url('assets/js/NumberFormatter.js') }}"></script>

<script>
   
    $(document).ready(function() {
        const dataTable = $('#Companies-datatable').DataTable({
            fixedColumns: { start: 1 },
            processing: true,
            serverSide: true,
            paging: true,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('companies.get-data') }}",
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {data: 'company_guid', name: 'company_guid', render: data => data || '-'},
                {data: 'alter_id', name: 'alter_id', render: data => data || '-'},
                {data: 'company_name', name: 'company_name', render: data => data || '-'},
                {data: 'license_number', name: 'license_number', render: data => data || '-'},
                { data: 'action', name: 'action', orderable: false, searchable: false },
           
            ],
            search: {
                orthogonal: { search: 'plain' }
            }
        });

        
        function sanitizeNumber(value) {
            return value ? value.toString().replace(/[^0-9.-]+/g, "") : "0";
        }

                $('#Companies-datatable').on('click', '.delete', function(){
                    var companyId = $(this).data('id');
                    var deleteRoute = $(this).data('route');
                    var button = $(this);
        
                    if(confirm("Are you sure you want to delete this company? This action cannot be undone.")) {
                        button.prop('disabled', true).text('Deleting...');
        
                        $.ajax({
                            url: deleteRoute,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                company_ids: companyId
                            },
                            success: function(response) {
                                if(response.success){
                                    dataTable.ajax.reload(null, false);
                                    alert(response.message);
                                } else {
                                    alert(response.message);
                                }
                                button.prop('disabled', false).text('Delete');
                            },
                            error: function(xhr, status, error) {
                                alert('An error occurred while deleting the company.');
                                button.prop('disabled', false).text('Delete');
                            }
                        });
                    }
                });
        
    });
</script>
@endsection
