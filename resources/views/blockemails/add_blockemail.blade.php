@extends("layouts.main")
@section('title', __('Send Mail | PreciseCA'))
@section("wrapper")
<div class="page-wrapper">
<div class="page-content pt-2">
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
        <div class="breadcrumb-title pe-3">Block Emails</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Add Email</li>
                </ol>
            </nav>
        </div>
    </div>
    <!--end breadcrumb-->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Add Blocked Email</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('block-email.store') }}" method="POST">
                @csrf
                <div class="mb-3"style="width:50%">
                    <label for="email" class="form-label">Email Address:</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                        id="email" name="email" placeholder="Enter email" value="" >
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3" style="width:50%">
                    <label for="remark" class="form-label">Remark</label>
                    <input type="text" class="form-control" 
                        id="remark" name="remark" placeholder="Enter Remark" >
                </div>
                <button type="submit" class="btn btn-primary w-20">Add Email</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div id="send-mail-wrapper" class="table-responsive">
                <table id="block-mail-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Remark') }}</th>
                            <th>{{ __('Date') }}</th>
                            {{-- <th>{{ __('Action') }}</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
@include('layouts.includes.datatable-js')

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<script>
    $(document).ready(function() {

        // DataTable (block-mail-table)
        var table1 = $('#block-mail-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('block-email.index') }}",
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'email', name: 'email' },
                { data: 'remark', name: 'remark' },
                { data: 'created_at', name: 'created_at' },
                // { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[1, 'asc']],
            language: {
                paginate: {
                    next: '<i class="ti ti-chevron-right"></i> next',
                    previous: '<i class="ti ti-chevron-left"></i> Prev',
                },
                lengthMenu: "{{ __('Show _MENU_ entries') }}",
                searchPlaceholder: "{{ __('Search...') }}",
            }
        });


            $('#send-all-btn').on('click', function () {
                var companyId = $('#company_id').val();
                var date = $('#date').val();

                console.log(companyId,date);

                $.ajax({
                    url: "{{ route('send-mutiple-email') }}",
                    method: "GET", // Change to GET for fetching data
                    data: {
                        company_id: companyId,
                        date: date,
                        _token: "{{ csrf_token() }}" // Optional for GET requests
                    }
                });
            });
        });
</script>
@endpush