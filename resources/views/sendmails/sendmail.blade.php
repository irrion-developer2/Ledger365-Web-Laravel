@extends("layouts.main")
@section('title', __('Send Mail | PreciseCA'))
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content pt-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Send Mail</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Send Mail</li>
                    </ol>
                </nav>
            </div>
            <button id="send-all-btn" class="btn btn-primary btn-sm ms-auto">Send Mail To All</button>
        </div>
        <!--end breadcrumb-->

        <div class="card">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

            <div class="card-body">
                <div class="row justify-content-between">
                    <div class="col-5 mb-3 d-flex">
                        <select name="company_id" id="company_id" class="form-select mx-2">
                        <option value="1">Select company</option>
                            @foreach($companys as $company)
                            <option value="{{ $company->company_id }}">{{ $company->company_name }}</option>
                            @endforeach
                        </select>
                        <input type="date" class="form-control mx-2" id="date" name="date" value="2022-04-01">
                    </div>
                    <div class="col-4">
                        <div class="alert" role="alert" style="display: none;">
                            <span id="emailmessage"></span>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="send-mail-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                {{-- <th>{{ __('Voucher ID') }}</th> --}}
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Phone Num') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Voucher Date') }}</th>
                                <th>{{ __('Company Name') }}</th>
                                <th>{{ __('Action') }}</th>
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
            // Initialize DataTable
            var table = $('#send-mail-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('sendmail') }}",
                    data: function (d) {
                        d.company_id = $('#company_id').val();
                        d.date = $('#date').val();
                    }
                },
                columns: [
                    { data: 'ledger_id', name: 'ledger_id' },
                    { data: 'ledger_name', name: 'ledger_name' },
                    //{ data: 'voucher_id', name: 'voucher_id' },
                    { data: 'email', name: 'email' },
                    { data: 'phone_number', name: 'phone_number' },
                    { data: 'amount', name: 'amount' },
                    { data: 'voucher_date', name: 'voucher_date' },
                    { data: 'company_name', name: 'company_name' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[3, 'asc']],
                language: {
                    paginate: {
                        next: '<i class="ti ti-chevron-right"></i> next',
                        previous: '<i class="ti ti-chevron-left"></i> Prev',
                    },
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    searchPlaceholder: "{{ __('Search...') }}",
                },
                initComplete: function () {
                    var searchInput = $('#send-mail-table_filter input[type="search"]');
                    searchInput
                        .removeClass('form-control form-control-sm')
                        .addClass('form-control ps-5 radius-30')
                        .attr('placeholder', 'Search Order');

                        $('#send-mail-table_filter label').contents().filter(function () {
                            return this.nodeType === 3; 
                        }).remove();

                    searchInput.wrap('<div class="position-relative pt-1"></div>');
                    searchInput.parent().append('<span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>');

                    var select = $('.dataTables_length select')
                        .removeClass('custom-select custom-select-sm form-control form-control-sm')
                        .addClass('form-select form-select-sm');
                },
                // dom: `
                //     <'dataTable-top row'
                //         <'dataTable-dropdown page-dropdown col-lg-3 col-sm-12'l>
                //         <'dataTable-botton table-btn col-lg-6 col-sm-12'B>
                //         <'dataTable-search tb-search col-lg-3 col-sm-12'f>
                //     >
                //     <'dataTable-container'<'col-sm-12'tr>>
                //     <'dataTable-bottom row'
                //         <'col-sm-5'i>
                //         <'col-sm-7'p>
                //     >
                // `,
            });
            $('#company_id, #date').on('change', function() {
                table.draw();
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