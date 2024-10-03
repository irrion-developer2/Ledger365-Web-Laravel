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
                {data: 'guid', name: 'guid', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'name', name: 'name', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'state', name: 'state', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {data: 'sub_id', name: 'sub_id', render: function(data, type, row) {
                    return data ? data : '-';
                }},
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-danger btn-sm delete-company" data-guid="${row.guid}">
                                    Delete
                                </button>`;
                    }
                },
            ],
            search: {
                orthogonal: {
                    search: 'plain'
                }
            }
        });

        $('#users-company-datatable').on('click', '.delete-company', function() {
            var guid = $(this).data('guid');
            if (confirm('Are you sure you want to delete this company and all related data?')) {
                $.ajax({
                    url: "{{ route('users-company.delete') }}", // Add the route for deletion
                    type: 'POST',
                    data: {
                        guid: guid,
                        _token: '{{ csrf_token() }}' // Add CSRF token for security
                    },
                    success: function(response) {
                        alert('Company deleted successfully!');
                        $('#users-company-datatable').DataTable().ajax.reload(); // Reload the DataTable
                    },
                    error: function(xhr) {
                        alert('Error deleting company!');
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
