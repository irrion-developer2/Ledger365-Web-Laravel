@extends("layouts.main")
@section('title', __('Customers | PreciseCA'))
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Customers</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Customers</li>
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
        $(document).ready(function() {
            var table = $('#customer-table').DataTable();

            $('#customer-table').on('click', '.dt-button', function() {
                var button = $(this).text();
                
                if (button.includes('Top Customer')) {
                    table.ajax.url('customers.index?filter=top_customers').load();
                } else if (button.includes('No Sales')) {
                    table.ajax.url('customers.index?filter=no_sales').load();
                }else if (button.includes('Reset')) {
                    table.ajax.url('{{ route('customers.index') }}').load();
                }
            });
        });
    </script>
@endpush