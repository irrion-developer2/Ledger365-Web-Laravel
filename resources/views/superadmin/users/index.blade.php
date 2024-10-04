@extends("layouts.main")
@section('title', __('Users | PreciseCA'))
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content pt-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Users</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->
      
        <div class="card">
            <div class="card-body">
                <div class="d-lg-flex align-items-center mb-4 gap-3">
                </div>
                <div class="table-responsive">
                    {{ $dataTable->table(['width' => '100%']) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('css')
    @include('layouts.includes.datatable-css')
@endpush
@push('javascript')
    @include('layouts.includes.datatable-js')
    {!! $dataTable->scripts() !!}

    <script>
        function changeStatus(userId, status) {
            $.ajax({
                url: '/update-user-status', // Replace with your route
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    user_id: userId,
                    status: status
                },
                success: function(response) {
                    if(response.success) {
                        $('#user-table').DataTable().ajax.reload(); // Reload table data
                    } else {
                        alert('Failed to update status.');
                    }
                },
                error: function() {
                    alert('An error occurred.');
                }
            });
        }
    </script>
@endpush