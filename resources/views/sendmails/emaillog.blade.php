@extends("layouts.main")
@section('title', __('Send Mail | PreciseCA'))
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content pt-2">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Email</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Email Log</li>
                    </ol>
                </nav>
            </div>
            <button id="send-all-btn" class="btn btn-primary btn-sm ms-auto" style="display:none;">Send Mail To All</button>
        </div>
        <!--end breadcrumb-->

        <div class="card">
            <div class="card-body">
                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="email-log-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('Sr.No.') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Company Name') }}</th>
                                <th>{{ __('Ledger Name') }}</th>
                                <th>{{ __('Ledger alias') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('status') }}</th>
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

        // Second DataTable (send-mail-table)
        var table1 = $('#email-log-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('email-log.getData') }}",
            },
            columns: [
                { data: 'email_id', name: 'email_id' },
                { data: 'created_at', name: 'created_at' },
                { data: 'company_id', name: 'company_id' },
                { data: 'ledger_name', name: 'ledger_name' },
                { data: 'ledger_id', name: 'ledger_id' },
                { data: 'email', name: 'email' },
                { data: 'ledger_id', name: 'ledger_id' },
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

        });

    </script>
@endpush