@extends("layouts.main")
@section('title', __('Users Details | PreciseCA'))

@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
@endsection

@section("wrapper")
    <div class="page-wrapper">
        <div class="page-content pt-2">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">Users Details</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Users Details</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive table-responsive-scroll border-0">
                        <div class="col-lg-6">
                            <h4 class="my-1 text-info">{{ $users->name }}</h4>
                        </div>
                        <table id="users-company-datatable" class="stripe row-border order-column" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Guid</th>
                                    <th>Alter Id</th>
                                    <th>Company Name</th>
                                    <th>State</th>
                                    <th>Sub Id</th>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function() {
        new DataTable('#users-company-datatable', {
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
                url: "{{ route('users-company.get-data', $users) }}",
                type: 'GET',
                data: function (d) {
                }
            },
            columns: [
                {data: 'company_guid', name: 'company_guid', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'alter_id', name: 'alter_id', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'company_name', name: 'company_name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'state', name: 'state', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'license_number', name: 'license_number', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'action', name: 'action', render: function(data, type, row) {
                    return data ? data : '-';
                }},
            ],
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

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
