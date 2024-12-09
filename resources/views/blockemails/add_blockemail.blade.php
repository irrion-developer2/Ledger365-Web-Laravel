@extends("layouts.main")
@section('title', __('Send Mail | PreciseCA'))
@section("wrapper")
<div class="page-wrapper">
<div class="container mt-4">
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
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Add Blocked Email</h5>
        </div>
        <div class="card-body w-50">
            <form action="{{ route('block-email.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address:</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                        id="email" name="email" placeholder="Enter email" value="" >
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="remark" class="form-label">Remark</label>
                    <input type="text" class="form-control" 
                        id="remark" name="remark" placeholder="Enter Remark" value="" >
                </div>
                <button type="submit" class="btn btn-primary w-20">Add Email</button>
            </form>
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