@extends("layouts.main")
@section('title', __('Stock Items | PreciseCA'))
@section("style")
<link href="{{ url('assets/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet" />
<link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .table-responsive-scroll {
        max-height: 500px; /* Set to your preferred height */
        overflow-y: auto;
        overflow-x: hidden !important; /* Optional, hides horizontal scrollbar */
        border: 1px solid #ddd;
    }
    .voucher-details {
        display: flex;
        flex-direction: column;
        margin-left: 0.5rem;
    }

    .voucher-number, .voucher-type {
        display: block;
    }
</style>
@endsection
@section("wrapper")
<div class="page-wrapper">
    <div class="page-content p-2">

        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
            <div class="breadcrumb-title pe-3">Stock Items</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Stock Items</li>
                        
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->


         <!--start email wrapper-->
         <div class="email-wrapper">
            <div class="email-sidebar">
                <div class="email-sidebar-header d-grid"> 
                    <a href="javascript:;" class="btn btn-primary compose-mail-btn" onclick="history.back();"><i class='bx bx-left-arrow-alt me-2'></i>{{ $totalCount }} All Items</a>
                </div>
                <div class="email-sidebar-content">
                    <div class="email-navigation" style="height: 530px;">
                        <div class="list-group list-group-flush">
                            @foreach($menuItems as $item)
                                <a href="{{ route('StockItem.items', ['StockItem' => $item->id]) }}" class="list-group-item d-flex align-items-center {{ request()->route('StockItem') == $item->id ? 'active' : '' }}" style="border-top: none;">
                                    <i class='bx {{ $item->icon ?? 'bx-default-icon' }} me-3 font-20'></i>
                                        <span>{{ $item->name }}</span>
                                    @if(isset($item->badge))
                                        <span class="badge bg-primary rounded-pill ms-auto">{{ $item->badge }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="email-header d-xl-flex align-items-center padding-0" style="height: auto;">
                <div class="d-flex align-items-center">
                    <div class="">
                        <h4 class="my-1 text-info">{{ $stockItem->name }} | {{ $stockItem->parent }}</h4>
                    </div>
                </div>
            </div>
            
            <div class="email-content py-2">
                <div class="">
                    <div class="email-list">
                       
                        <div class="col-lg-12">
                            <div class="col">
                                <div class="card radius-10 border-start border-0 border-4 border-info">
                                    <div class="card-body">
                                        <div class="row p-2">
                                            <div class="col-lg-9" style="padding: 25px;background: #eee;border-bottom-left-radius: 15px;border-top-left-radius: 15px;">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <p class="mb-0 font-13">Stock on hand</p>
                                                        <h6>
                                                            {{-- {{ $stockOnHandValue ? number_format(ceil($stockOnHandValue * 100) / 100, 2) : '-' }} --}}
                                                            {{ $stockOnHandValue ? number_format(($stockOnHandValue * 100) / 100, 2) : '0.00' }}
                                                            | 
                                                            {{ $stockOnHandBalance ?? '-' }} 
                                                            @php
                                                            $unit = $stockItem->unit ?? $stockItem->pluck('unit')->filter()->first();
                                                            @endphp
                                                            {{ $unit ?? '-' }}

                                                        </h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3" style="padding: 25px;background: #e7d9d9;border-bottom-right-radius: 15px;border-top-right-radius: 15px;">
                                                <div class="col-lg-12">
                                                    <a href="{{ route('SaleStockItem.items', ['SaleStockItem' => $stockItem->id]) }}" class="btn btn-outline-primary border-1">
                                                        <span>Sales By Items</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 px-2">
                            <div class="col">
                                <div class="accordion" id="accordionExample">

                                    @include('superadmin.stock-items.accordion._accordion_item_one')
                                    @include('superadmin.stock-items.accordion._accordion_item_two') 
                                    @include('superadmin.stock-items.accordion._accordion_item_three') 
                                    {{-- @include('superadmin.stock-items.accordion._accordion_item_four')  --}}
                                    
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!--end email wrapper-->           
    </div>
</div>
@endsection
@push('css')
@include('layouts.includes.datatable-css')
@endpush
@push('javascript')
<script>
	new PerfectScrollbar('.email-navigation');
	new PerfectScrollbar('.email-list');
</script>
@include('layouts.includes.datatable-js')


@endpush
@section("script")
<script src="{{ url('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ url('assets/plugins/bs-stepper/js/main.js') }}"></script>
@endsection