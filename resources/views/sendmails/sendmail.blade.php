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
            <button id="send-all-btn" class="btn btn-primary btn-sm ms-auto" style="display:none;">Send Mail To All</button>
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
                    <div class="col-3 mb-3 d-flex">
                        <button id="back" class="btn btn-primary btn-sm ms-auto" style="display:none;">back</button>
                        <select name="company_id" id="company_id" class="form-select mx-2" style="display:none;">
                        <option>Select company</option>
                            @foreach($companys as $company)
                                <option value="{{ $company->company_id }}">{{ $company->company_name }}</option>
                            @endforeach
                        </select>
                        <!-- value="<?php echo date('Y-m-01'); ?>" -->
                        <input type="date" class="form-control mx-2" id="date" name="date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-4">
                        <div class="alert" role="alert" style="display: none;">
                            <span id="emailmessage"></span>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <div id="companys-wrapper" class="table-responsive">
                    <table id="companys" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('Company Name') }}</th>
                                <th>{{ __('Ledger') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Bill') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div id="send-mail-wrapper" class="table-responsive" style="display:none;">
                    <table id="send-mail-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
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
            var table1 = $('#companys').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sendmail') }}",
                data: function (d) {
                    d.load_companys = true;
                    d.company_id = $('#company_id').val();
                    d.date = $('#date').val();
                }
            },
            columns: [
                { data: 'company_name', name: 'company_name' },
                { data: 'ledger', name: 'ledger' },
                { data: 'email', name: 'email' },
                { data: 'bill', name: 'bill' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
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

        // Second DataTable (send-mail-table)
        var table2 = $('#send-mail-table').DataTable({
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
                { data: 'email', name: 'email' },
                { data: 'phone_number', name: 'phone_number' },
                { data: 'amount', name: 'amount' },
                { data: 'voucher_date', name: 'voucher_date' },
                { data: 'company_name', name: 'company_name' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            rowCallback: function (row, data) {
                if (data.blocked_email) {
                    $(row).css({
                        'color': 'red'
                    });
                    $(row).find('.mail, .whatsapp').addClass('disabled');
                }
            },
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

        // Trigger refresh for both tables when filters change
        $('#company_id, #date').on('change', function () {
            table1.draw();
            table2.draw();
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

        $(document).off('click', '.count-mutliple-mail').on('click', '.count-mutliple-mail', function () {
            var companyId = $(this).data('company-id');
            var date = $(this).data('date');

            console.log('Company ID:', companyId, 'Date:', date);

            $.ajax({
                url: "{{ route('count-mutliple-mail') }}",
                method: "GET",
                data: {
                    company_id: companyId,
                    date: date,
                    _token: "{{ csrf_token() }}"
                }
            });
        });

        $(document).on('click', '.view-details', function () {
            let companyId = $(this).data('company-id');
            let date = $(this).data('date');
            console.log('Company ID:', companyId, 'Date:', date);

            $('#companys-wrapper').hide();
            $('#send-mail-wrapper').show();
            $('#send-all-btn').show();
            $('#company_id').show();
            // $('#back').show();
            $('#company_id').val(companyId);
            $('#company_id').trigger('change');
        });
        
        // $(document).on('click', '#back', function () {
        //     $('#send-mail-wrapper').hide();
        //     $('#send-all-btn').hide();
        //     $('#back').hide();
        //     $('#companys-wrapper').show();
        // });

    </script>
@endpush