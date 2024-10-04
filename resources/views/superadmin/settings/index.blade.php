@extends("layouts.main")
@section('title', __('Settings | PreciseCA'))
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Settings</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->
      
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-4">License Details</h5>
                        <form action="{{ route('settings.license.save') }}" method="POST">
                            @csrf 
                            <input type="hidden" name="super_admin_user_id" class="form-control" value="{{ auth()->user()->id }}">

                            <div class="row mb-3">
                                <label for="input35" class="col-sm-3 col-form-label">Enter License Key</label>
                                <div class="col-sm-9">
                                    <input type="text" name="license_number" class="form-control" id="input35" placeholder="Enter License Key">
                                </div>
                            </div>
                            
                            <div class="row">
                                <label class="col-sm-3 col-form-label"></label>
                                <div class="col-sm-9">
                                    <div class="d-md-flex d-grid align-items-center gap-3">
                                        <button type="submit" class="btn btn-primary px-4">Submit</button>
                                        <button type="button" class="btn btn-light px-4" onclick="history.back();">Back</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
@push('javascript')
   
@endpush