@extends("layouts.main")
@section('title', __('Company Mapping | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Company Mapping</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Company Mapping</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive table-responsive-scroll border-0">
                        <div class="col-lg-6">
                            <h4 class="my-1 text-info">{{ $userMapping->name }}</h4>
                        </div>

                        <table id="users-company-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    {{--  <th>Guid</th>
                                    <th>Alter Id</th>  --}}
                                    <th>Company Name</th>
                                    <th>State</th>
                                    <th>License Number </th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be populated by AJAX --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    {{--  <th></th>
                                    <th></th>  --}}
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>

                        <form id="company-mapping-form">
                            @csrf
                            <table id="company-mapping-datatable" class="stripe row-border order-column" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        {{--  <th>Guid</th>
                                        <th>Alter Id</th>  --}}
                                        <th>Company Name</th>
                                        <th>State</th>
                                        <th>License Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data will be populated by DataTables AJAX --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        {{--  <th>Guid</th>
                                        <th>Alter Id</th>  --}}
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Save Mappings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("script")
@include('layouts.includes.datatable-js-css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
    $(document).ready(function() {
        var usersCompanyTable = new DataTable('#users-company-datatable', {
            fixedColumns: {
                start: 1,
            },
            processing: true,
            serverSide: true,
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('users-company.get-data', $userMapping->id) }}",
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {{--  {data: 'company_guid', name: 'company_guid', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'alter_id', name: 'alter_id', render: function(data, type, row) {
                    return data ? data : '-';
                }},  --}}
                {data: 'company_name', name: 'company_name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'state', name: 'state', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'license_number', name: 'license_number', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'action', name: 'action', orderable: false, searchable: false, render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        // Handle Delete Action in Users Company DataTable
        $('#users-company-datatable').on('click', '.delete', function(){
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
                            usersCompanyTable.ajax.reload(null, false);
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                        button.prop('disabled', false).text('Delete');
                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred while deleting the company.');
                        button.prop('disabled', false).text('Delete');
                    }
                });
            }
        });

        // Initialize Company Mapping DataTable
        var companyMappingTable = new DataTable('#company-mapping-datatable', {
            fixedColumns: {
                start: 1,
            },
            processing: true,
            serverSide: true,
            paging: false,
            scrollCollapse: true,
            scrollX: true,
            scrollY: 300,
            ajax: {
                url: "{{ route('companiesMapping.data', $userMapping->id) }}",
                type: 'GET',
                data: function (d) {
                    // Additional parameters if needed
                }
            },
            columns: [
                {
                    data: 'mapped',
                    name: 'mapped',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<input type="checkbox" class="company-checkbox" name="company_ids[]" value="${row.company_id}" ${data ? 'checked' : ''}>`;
                    }
                },
                {{--  {data: 'company_guid', name: 'company_guid', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'alter_id', name: 'alter_id', render: function(data, type, row) {
                    return data ? data : '-';
                }},  --}}
                {data: 'company_name', name: 'company_name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'state', name: 'state', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'license_number', name: 'license_number', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        // Handle Select All Checkbox in Company Mapping DataTable
        $('#select-all').on('click', function(){
            var isChecked = $(this).is(':checked');
            $('.company-checkbox').prop('checked', isChecked);
        });

        // Update Select All Checkbox Status in Company Mapping DataTable
        $('#company-mapping-datatable tbody').on('change', '.company-checkbox', function(){
            if(!$(this).is(':checked')){
                $('#select-all').prop('checked', false);
            } else {
                if($('.company-checkbox:checked').length === $('.company-checkbox').length){
                    $('#select-all').prop('checked', true);
                }
            }
        });

        // Handle Company Mapping Form Submission
        $('#company-mapping-form').on('submit', function(e){
            e.preventDefault();

            var selectedCompanies = [];
            $('.company-checkbox:checked').each(function(){
                selectedCompanies.push($(this).val());
            });

            // Disable the submit button to prevent multiple submissions
            $('button[type="submit"]').prop('disabled', true).text('Saving...');

            // Send AJAX request to update mappings
            $.ajax({
                url: "{{ route('companiesMapping.update', $userMapping->id) }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    company_ids: selectedCompanies
                },
                success: function(response) {
                    if(response.success){
                        toastr.success(response.message);
                        // Reload both DataTables to reflect changes
                        usersCompanyTable.ajax.reload(null, false);
                        companyMappingTable.ajax.reload(null, false);
                    } else {
                        toastr.error('An error occurred while updating the mappings.');
                    }
                    $('button[type="submit"]').prop('disabled', false).text('Save Mappings');
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'An error occurred while updating the mappings.';
                    if(xhr.responseJSON && xhr.responseJSON.message){
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    $('button[type="submit"]').prop('disabled', false).text('Save Mappings');
                }
            });
        });
    });
</script>

@endsection
